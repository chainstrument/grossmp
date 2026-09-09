<?php

namespace App\DataFixtures;

use App\Catalog\Domain\Entity\PriceTier;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Enum\ProductCategory;
use App\Catalog\Domain\ValueObject\Money;
use App\Catalog\Domain\ValueObject\Sku;
use App\Entity\Company;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Company $restoChezMarcel */
        $restoChezMarcel = $this->getReference(CompanyFixtures::COMPANY_RESTO_CHEZ_MARCEL, Company::class);

        $pastaSauce = new Product(
            new Sku('SAU-TOM-500'),
            'Sauce tomate 500g',
            ProductCategory::FOOD,
            Money::fromEuros(1.90),
        );
        $pastaSauce->setDescription('Sauce tomate cuisinée, bocal 500g.');
        $pastaSauce->adjustStock(400);
        // Generic degressive pricing, open to any client.
        $pastaSauce->addPriceTier(new PriceTier(10, Money::fromEuros(1.70)));
        $pastaSauce->addPriceTier(new PriceTier(50, Money::fromEuros(1.45)));
        // Negotiated rate for a specific client, wins over the generic tiers above.
        $pastaSauce->addPriceTier(new PriceTier(10, Money::fromEuros(1.30), $restoChezMarcel));
        $manager->persist($pastaSauce);

        $mineralWater = new Product(
            new Sku('EAU-MIN-150'),
            'Eau minérale 1,5L (pack de 6)',
            ProductCategory::BEVERAGES,
            Money::fromEuros(3.20),
        );
        $mineralWater->adjustStock(250);
        $mineralWater->addPriceTier(new PriceTier(20, Money::fromEuros(2.90)));
        $manager->persist($mineralWater);

        $dishSoap = new Product(
            new Sku('LIQ-VAI-750'),
            'Liquide vaisselle 750ml',
            ProductCategory::HOUSEHOLD,
            Money::fromEuros(2.10),
        );
        $dishSoap->adjustStock(180);
        $manager->persist($dishSoap);

        $handSoap = new Product(
            new Sku('SAV-MAI-500'),
            'Savon pour les mains 500ml',
            ProductCategory::HYGIENE,
            Money::fromEuros(2.60),
        );
        $handSoap->adjustStock(120);
        $manager->persist($handSoap);

        $cardboardBoxes = new Product(
            new Sku('CAR-EMB-M'),
            'Caisses carton (lot de 25, format M)',
            ProductCategory::PACKAGING,
            Money::fromEuros(14.50),
        );
        $cardboardBoxes->adjustStock(60);
        $cardboardBoxes->addPriceTier(new PriceTier(5, Money::fromEuros(12.90)));
        $manager->persist($cardboardBoxes);

        $outOfStockItem = new Product(
            new Sku('BIS-CHO-300'),
            'Biscuits chocolat 300g',
            ProductCategory::FOOD,
            Money::fromEuros(2.40),
        );
        // No adjustStock() call: starts at 0, demonstrates the out-of-stock case.
        $manager->persist($outOfStockItem);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CompanyFixtures::class];
    }
}
