<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud) // TODO: Change the autogenerated stub
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnIndex()->hideOnForm(),
            ImageField::new('avatar')
                ->setBasePath('images/avatars')
                ->setUploadDir('public\images\avatars'),
            EmailField::new('email', 'Adresse e-mail'),
            TextField::new('password', 'Mot de passe')->onlyWhenCreating(),
            TextField::new('firstname', 'Prénom'),
            TextField::new('lastname', 'Nom'),
            DateField::new('birthday', 'Date d\'anniversaire')
        ];
    }
}
