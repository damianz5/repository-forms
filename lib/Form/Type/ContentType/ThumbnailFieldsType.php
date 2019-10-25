<?php

namespace EzSystems\RepositoryForms\Form\Type\ContentType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ThumbnailFieldsType extends AbstractType
{
    public function getParent()
    {
        return TextType::class;
    }
}