<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Service;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The backend user context a {@see \TYPO3\CMS\Core\DataHandling\DataHandler} run needs
 * from the CLI, where there is none.
 *
 * `GeocodeCommand` writes through the DataHandler rather than the repository, because
 * the coordinate columns are `allowLanguageSynchronization` and only the DataHandler
 * runs `DataMapProcessor` - a repository write reaches the default record and leaves
 * every translation behind (ACE-709).
 *
 * Three traps, each of which costs a silent wrong result rather than an error:
 *
 * 1. Passing the user to `DataHandler::start()` is not enough. Parts of the
 *    localization path go through `BackendUtility` helpers that read
 *    `$GLOBALS['BE_USER']` directly and ignore the injected object, so the global is
 *    swapped in for the duration of the run and restored in a `finally` - also when
 *    the callback throws.
 * 2. `BackendUserAuthentication::$workspace` defaults to **-99** ("offline"), not to
 *    live. A synthetic user that never gets a workspace assigned would make the
 *    DataHandler act in a workspace that does not exist, so it is always set.
 * 3. DataHandler error paths render backend labels through `$GLOBALS['LANG']`, which
 *    is therefore set defensively when nothing else provided it.
 *
 * Geocoding repairs live data, so the run is forced into the live workspace rather
 * than inheriting one from whoever happens to be logged in.
 *
 * Stateless: all run state lives in local variables and callback arguments.
 *
 * @internal owned by the geocoding of EXT:academic_partners, no public API.
 */
final class GeocodeWriteContext
{
    public function __construct(
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {}

    /**
     * Executes $action with a guaranteed `$GLOBALS['BE_USER']` acting in the live
     * workspace, restoring the previous global state afterwards.
     *
     * @param \Closure(BackendUserAuthentication): void $action
     */
    public function runAsLiveBackendUser(\Closure $action): void
    {
        $previousBackendUser = $GLOBALS['BE_USER'] ?? null;
        $previousLanguageService = $GLOBALS['LANG'] ?? null;

        $backendUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $backendUser->user = [
            'uid' => 0,
            'admin' => 1,
            'username' => '_partner_geocoder_',
        ];
        $backendUser->workspace = 0;

        $GLOBALS['BE_USER'] = $backendUser;
        $GLOBALS['LANG'] ??= $this->languageServiceFactory->create('default');

        try {
            $action($backendUser);
        } finally {
            $GLOBALS['BE_USER'] = $previousBackendUser;
            if ($previousBackendUser === null) {
                unset($GLOBALS['BE_USER']);
            }
            $GLOBALS['LANG'] = $previousLanguageService;
            if ($previousLanguageService === null) {
                unset($GLOBALS['LANG']);
            }
        }
    }
}
