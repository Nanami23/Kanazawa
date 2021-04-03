<?php

namespace App\Controller\Admin;

use App\Entity\Card;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CardCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Card::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud) // TODO: Change the autogenerated stub
            ->setEntityLabelInSingular('Carte')
            ->setEntityLabelInPlural('Cartes');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnIndex()->hideOnForm(),
            ImageField::new('picture', 'Aperçu')
                ->setBasePath('images/cards')
                ->setUploadDir('public\images\cards'),
            TextField::new('picture', 'Nom')->hideOnForm(),
            NumberField::new('number', 'Numéro'),
            TextField::new('name')
        ];
    }
}