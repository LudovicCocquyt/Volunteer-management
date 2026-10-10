<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class AttachmentFormType extends AbstractType
{
    /**
     * Types autorisés => extension enregistrée sur le disque.
     * L'extension est déduite du contenu réel du fichier, jamais du nom envoyé.
     */
    public const ALLOWED_TYPES = [
        'application/pdf'                                                         => 'pdf',
        'image/jpeg'                                                              => 'jpg',
        'image/png'                                                               => 'png',
        'image/webp'                                                              => 'webp',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.oasis.opendocument.text'                                 => 'odt',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'       => 'xlsx',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', FileType::class, [
            'label'       => 'Ajouter un fichier',
            'mapped'      => false,
            'required'    => true,
            'constraints' => [
                new Assert\NotNull(message: 'Choisissez un fichier.'),
                new Assert\File(
                    maxSize: '5M',
                    mimeTypes: array_keys(self::ALLOWED_TYPES),
                    maxSizeMessage: 'Le fichier est trop lourd ({{ size }} {{ suffix }}), maximum {{ limit }} {{ suffix }}.',
                    mimeTypesMessage: 'Type de fichier non autorisé. Formats acceptés : PDF, JPG, PNG, WebP, DOCX, ODT, XLSX.',
                ),
            ],
        ]);
    }
}
