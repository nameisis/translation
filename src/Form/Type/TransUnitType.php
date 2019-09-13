<?php

namespace Selonia\TranslationBundle\Form\Type;

use Selonia\TranslationBundle\Entity\Domain;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransUnitType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('key', TextType::class, [
            'label' => 'translations.key',
        ]);
        $builder->add('domain', EntityType::class, [
            'label' => 'translations.domain',
            'class' => Domain::class,
        ]);
        $builder->add('translations', CollectionType::class, [
            'entry_type' => TranslationType::class,
            'label' => 'translations.page_title',
            'required' => false,
            'entry_options' => [
                'data_class' => $options['translation_class'],
            ],
        ]);
        $builder->add('save', SubmitType::class, [
            'label' => 'translations.save',
        ]);
        $builder->add('save_add', SubmitType::class, [
            'label' => 'translations.save_add',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
            'default_domain' => ['messages'],
            'domains' => [],
            'translation_class' => null,
            'translation_domain' => 'NameisisTranslationBundle',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'nameisis_trans_unit';
    }
}
