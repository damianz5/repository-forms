<?php

/**
 * This file is part of the eZ RepositoryForms package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RepositoryForms\FieldType\Mapper;

use EzSystems\RepositoryForms\Data\Content\FieldData;
use EzSystems\RepositoryForms\Data\FieldDefinitionData;
use EzSystems\RepositoryForms\FieldType\FieldDefinitionFormMapperInterface;
use EzSystems\RepositoryForms\FieldType\FieldValueFormMapperInterface;
use EzSystems\RepositoryForms\Form\DataTransformer\MultilingualSelectionTransformer;
use EzSystems\RepositoryForms\Form\EventListener\SelectionMultilingualOptionsDataListener;
use EzSystems\RepositoryForms\Form\Type\FieldType\SelectionFieldType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectionFormMapper implements FieldDefinitionFormMapperInterface, FieldValueFormMapperInterface
{
    /**
     * Selection items can be added and removed, the collection field type is used for this.
     * - An empty field is always present, if this is filled it will become a new entry.
     * - If a filled field is cleared the entry will be removed.
     * - Only one new entry can be added per page load (while any number can be removed).
     *   This can be improved using a template override with javascript code.
     * - The prototype_name option is for the empty field which is used for new items. If not
     *   using javascript, it must be unique.
     * - Data for 'options' field can now be supplied either by `options` property_path or by
     *   `multilingualOptions` if those are provided.
     *   `multilingualOptions` is an array with keys equal to used languageCodes.
     */
    public function mapFieldDefinitionForm(FormInterface $fieldDefinitionForm, FieldDefinitionData $data)
    {
        $isTranslation = $data->contentTypeData->languageCode !== $data->contentTypeData->mainLanguageCode;
        $options = $fieldDefinitionForm->getConfig()->getOptions();
        $languageCode = $options['languageCode'];
        $isMultilingual = isset($data->fieldDefinition->fieldSettings['multilingualOptions']);
        $dataPropertyPathName = $isMultilingual ? 'multilingualOptions' : 'options';

        $fieldDefinitionForm
            ->add('isMultiple', CheckboxType::class, [
                'required' => false,
                'property_path' => 'fieldSettings[isMultiple]',
                'label' => 'field_definition.ezselection.is_multiple',
                'disabled' => $isTranslation,
            ]);

        $formBuilder = $fieldDefinitionForm->getConfig()->getFormFactory()->createBuilder();

        $optionField = $formBuilder->create('options', CollectionType::class, [
            'entry_type' => TextType::class,
            'entry_options' => ['required' => false],
            'allow_add' => true,
            'allow_delete' => true,
            'delete_empty' => false,
            'prototype' => true,
            'prototype_name' => '__number__',
            'required' => false,
            'property_path' => 'fieldSettings[' . $dataPropertyPathName . ']',
            'label' => 'field_definition.ezselection.options',
        ]);

        if ($isMultilingual) {
            $dataListener = new SelectionMultilingualOptionsDataListener($languageCode);
            $dataTransformer = new MultilingualSelectionTransformer($languageCode, $data);

            $optionField
                ->addEventListener(
                    FormEvents::PRE_SET_DATA,
                    [$dataListener, 'setLanguageOptions'],
                    10
                )
                ->addModelTransformer(
                    $dataTransformer
                );
        }

        $fieldDefinitionForm->add(
            $optionField
                ->setAutoInitialize(false)
                ->getForm()
        );
    }

    public function mapFieldValueForm(FormInterface $fieldForm, FieldData $data)
    {
        $fieldDefinition = $data->fieldDefinition;
        $formConfig = $fieldForm->getConfig();
        $languageCode = $fieldForm->getConfig()->getOption('languageCode');

        $choices = $fieldDefinition->fieldSettings['options'];

        if (!empty($fieldDefinition->fieldSettings['multilingualOptions'][$languageCode])) {
            $choices = $fieldDefinition->fieldSettings['multilingualOptions'][$languageCode];
        } elseif (!empty($fieldDefinition->fieldSettings['multilingualOptions'][$fieldDefinition->mainLanguageCode])) {
            $choices = $fieldDefinition->fieldSettings['multilingualOptions'][$fieldDefinition->mainLanguageCode];
        }

        $fieldForm
            ->add(
                $formConfig->getFormFactory()->createBuilder()
                    ->create(
                        'value',
                        SelectionFieldType::class,
                        [
                            'required' => $fieldDefinition->isRequired,
                            'label' => $fieldDefinition->getName(),
                            'multiple' => $fieldDefinition->fieldSettings['isMultiple'],
                            'choices' => array_flip($choices),
                        ]
                    )
                    ->setAutoInitialize(false)
                    ->getForm()
            );
    }

    /**
     * Fake method to set the translation domain for the extractor.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'translation_domain' => 'ezrepoforms_content_type',
            ]);
    }
}
