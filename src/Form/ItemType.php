<?php

namespace App\Form;

use App\Entity\Item;
use App\Entity\Unit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ItemType extends AbstractType
{
  public function __construct(
    private UrlGeneratorInterface $router,
  ) {}

  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('item_code', TextType::class, [
        'label' => 'Item Code',
        'required' => true,
      ])
      ->add('item_desc', TextType::class, [
        'label' => 'Item Desc',
        'required' => false,
      ])
      ->add('item_notes', TextareaType::class, [
        'label' => 'Item Notes',
        'required' => false,
      ])
      // single_text widget enables the browser datepicker
      ->add('item_exp_date', DateType::class, [
        'widget' => 'single_text',
        'label' => 'Item Exp. Date',
        'required' => false,
      ])
      ->add('item_unit', EntityType::class, [
        'class' => Unit::class,
        'choice_label' => 'unit_code',
        'label' => 'Item Unit',
        'required' => false,
      ])
      ->add('item_qty', TextType::class, [
        'label' => 'Item Total Qty.',
        'disabled' => true,
      ])
      ->add('new', SubmitType::class, [
        'label' => 'New',
        'attr' => [
          'form' => $builder->getName() . '_form',
          'formaction' => $this->router->generate('item_list'),
          'formmethod' => 'post',
        ],
      ])
      ->add('search', SubmitType::class, [
        'label' => 'Search',
        'attr' => [
          'form' => $builder->getName() . '_form',
          'formaction' => $this->router->generate('item_search'),
          'formmethod' => 'post',
        ],
      ])
      ->add('save', SubmitType::class, [
        'label' => 'Save',
        'attr' => [
          'form' => $builder->getName() . '_form',
          'formaction' => $this->router->generate('item_save'),
          'formmethod' => 'post',
        ],
      ])
      ->add('delete', SubmitType::class, [
        'label' => 'Delete',
        'attr' => [
          'form' => $builder->getName() . '_form',
          'formaction' => $this->router->generate('item_delete'),
          'formmethod' => 'post',
        ],
      ])
    ;
  }


  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => Item::class,
      'attr' => ['id' => 'item_form']
    ]);
  }
}
