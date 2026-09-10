<?php

namespace App\Ordering\UI\Form;

use App\Catalog\Domain\Entity\Product;
use App\Ordering\Application\AddToCartRequest;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddToCartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => fn (Product $product) => sprintf(
                    '[%s] %s (stock : %d)',
                    $product->getReference(),
                    $product->getName(),
                    $product->getStockQuantity()
                ),
                'label' => 'Produit',
                'placeholder' => 'Choisir un produit',
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => ['min' => 1],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AddToCartRequest::class,
        ]);
    }
}
