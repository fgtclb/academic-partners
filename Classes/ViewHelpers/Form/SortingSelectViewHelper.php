<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\ViewHelpers\Form;

use FGTCLB\AcademicPartners\Enumeration\SortingOptions;
use FGTCLB\CategoryTypes\ViewHelpers\Form\AbstractSelectViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Renders sorting option select field markup (string) with options array passed in
 * as `options` ViewHelper argument or using SortingOptions::getConstants() otherwise.
 */
final class SortingSelectViewHelper extends AbstractSelectViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $arguments = [
            'type' => [
                'type' => 'string',
                'defaultValue' => 'combined',
                'description' => 'Allowed values are combined, fields or directions.',
            ],
            'l10n' => [
                'type' => 'string',
                'description' => 'If specified, will call the correct label specified in locallang file.',
            ],
            'extensionName' => [
                'type' => 'string',
                'defaultValue' => 'academic_partners',
                'description' => 'If set, the translation function will use the language labels from the given extension.',
            ],
        ];

        $this->registerArguments($arguments);
    }

    /**
     * @return array<int|string, array{value: int|string, label: string, isSelected: bool}>
     */
    protected function getOptions(): array
    {
        $options = $this->hasArgumentOptions() ? $this->createOptionsFromArguments() : $this->createDefaultSortingOptions();
        if ($this->arguments['sortByOptionLabel'] !== false) {
            usort($options, fn($a, $b) => strcoll((string)$a['label'], (string)$b['label']));
        }

        return $options;
    }

    private function hasArgumentOptions(): bool
    {
        return is_array($this->arguments['options']) && $this->arguments['options'] !== [];
    }

    /**
     * @return array<int|string, array{value: int|string, label: string, isSelected: bool}>
     */
    private function createOptionsFromArguments(): array
    {
        $options = [];
        if (!$this->hasArgumentOptions()) {
            return $options;
        }
        foreach ($this->arguments['options'] as $value => $label) {
            if (isset($this->arguments['l10n']) && $this->arguments['l10n']) {
                $label = $this->translateLabel($label, $this->arguments['l10n']);
            }
            $options[$value] = [
                'value' => $value,
                'label' => $label,
                'isSelected' => $this->isSelected($value),
            ];
        }
        return $options;
    }

    /**
     * @return array<int|string, array{value: int|string, label: string, isSelected: bool}>
     */
    private function createDefaultSortingOptions(): array
    {
        $options = [];
        foreach (SortingOptions::getConstants() as $sortingValue) {
            $value = $sortingValue;
            $labelKey = str_replace(' ', '.', $sortingValue);

            if ($this->arguments['type'] !== 'combined') {
                [$sortingField, $sortingDirection] = GeneralUtility::trimExplode(' ', $sortingValue);
                if ($this->arguments['type'] === 'fields') {
                    $value = $sortingField;
                    $labelKey = 'field.' . $sortingField;
                } elseif ($this->arguments['type'] === 'directions') {
                    $value = $sortingDirection;
                    $labelKey = 'direction.' . $sortingDirection;
                }
            }

            $options[$value] = [
                'value' => $value,
                'label' => $this->translateLabel($labelKey),
                'isSelected' => $this->isSelected($value),
            ];
        }
        return $options;
    }

    /**
     * @param array<int|string, array{value: int|string, label: string, isSelected: bool}> $options
     * @return string
     */
    protected function renderOptionTags($options): string
    {
        $output = '';
        foreach ($options as $option) {
            $output .= '<option value="' . $option['value'] . '"';
            if ($option['isSelected']) {
                $output .= ' selected="selected"';
            }
            $output .= '>' . htmlspecialchars((string)$option['label']) . '</option>' . LF;
        }
        return $output;
    }

    protected function translateLabel(
        string $labelKey,
        ?string $l10nPrefix = 'sorting'
    ): string {
        $key = sprintf(
            'LLL:EXT:academic_partners/Resources/Private/Language/locallang.xlf:%s.%s',
            $l10nPrefix,
            $labelKey
        );

        $translatedLabel = LocalizationUtility::translate(
            $key,
            $this->arguments['extensionName']
        );

        if ($translatedLabel === null) {
            return $labelKey;
        }

        return $translatedLabel;
    }
}
