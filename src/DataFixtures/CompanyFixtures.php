<?php

namespace App\DataFixtures;

use App\Identity\Domain\Entity\Company;
use App\Identity\Domain\ValueObject\Address;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CompanyFixtures extends Fixture
{
    public const COMPANY_SUPERETTE_DU_COIN = 'company-superette-du-coin';
    public const COMPANY_RESTO_CHEZ_MARCEL = 'company-resto-chez-marcel';

    public function load(ObjectManager $manager): void
    {
        $superette = new Company();
        $superette->setName('Supérette du Coin');
        $superette->setSiret('40483304800021');
        $superette->setBillingAddress($this->address('12 rue des Halles', '75001', 'Paris'));
        $superette->setShippingAddress($this->address('12 rue des Halles', '75001', 'Paris'));
        $manager->persist($superette);
        $this->addReference(self::COMPANY_SUPERETTE_DU_COIN, $superette);

        $resto = new Company();
        $resto->setName('Chez Marcel');
        $resto->setSiret('55208131700018');
        $resto->setBillingAddress($this->address('5 avenue Jean Jaurès', '69007', 'Lyon'));
        $resto->setShippingAddress($this->address('8 rue de la Cuisine', '69007', 'Lyon'));
        $manager->persist($resto);
        $this->addReference(self::COMPANY_RESTO_CHEZ_MARCEL, $resto);

        $manager->flush();
    }

    private function address(string $street, string $postalCode, string $city): Address
    {
        $address = new Address();
        $address->setStreet($street);
        $address->setPostalCode($postalCode);
        $address->setCity($city);
        $address->setCountry('FR');

        return $address;
    }
}
