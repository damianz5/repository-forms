<?php
/**
 * This file is part of the eZ RepositoryForms package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RepositoryForms\Form\ActionDispatcher;

use eZ\Publish\API\Repository\Values\Content\Location;
use EzSystems\RepositoryForms\Event\RepositoryFormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentDispatcher extends AbstractActionDispatcher
{
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['referrerLocation']);
        $resolver->setDefault('referrerLocation', null);
        $resolver->setAllowedTypes('referrerLocation', [Location::class, 'null']);
    }

    protected function getActionEventBaseName()
    {
        return RepositoryFormEvents::CONTENT_EDIT;
    }
}
