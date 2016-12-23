<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace OnlineShop\Framework\PriceSystem\TaxManagement;


use OnlineShop\Framework\Exception\UnsupportedException;
use OnlineShop\Framework\PriceSystem\IPrice;

/**
 * Class TaxCalculationService
 */
class TaxCalculationService {

    const CALCULATION_FROM_NET = "net";
    const CALCULATION_FROM_GROSS = "gross";

    public static function updateTaxes(IPrice $price, $calculationMode = self::CALCULATION_FROM_NET) {

        switch ($calculationMode) {
            case self::CALCULATION_FROM_NET:
                return self::calculationFromNet($price);
            case self::CALCULATION_FROM_GROSS:
                return self::calculationFromGross($price);
            default:
                throw new UnsupportedException("Calculation Mode [" . $calculationMode . "] not supported.");
        }

    }

    protected static function calculationFromNet(IPrice $price) {

        switch ($price->getTaxEntryCombinationMode()) {
            case TaxEntry::CALCULATION_MODE_COMBINE:

                $taxEntries = $price->getTaxEntries();
                $netAmount = $price->getNetAmount();
                $grossAmount = $netAmount;

                if($taxEntries) {
                    foreach ($taxEntries as $entry) {
                        $amount = $netAmount * $entry->getPercent() / 100;
                        $entry->setAmount($amount);
                        $grossAmount += $amount;
                    }

                    $price->setGrossAmount($grossAmount);

                } else {
                    $price->setGrossAmount($netAmount);
                }



                break;

            case TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER:

                $taxEntries = $price->getTaxEntries();
                $netAmount = $price->getNetAmount();
                $grossAmount = $netAmount;

                if($taxEntries) {
                    foreach($taxEntries as $entry) {
                        $amount = $grossAmount * $entry->getPercent() / 100;
                        $entry->setAmount( $amount );
                        $grossAmount += $amount;
                    }
                    $price->setGrossAmount($grossAmount);

                } else {
                    $price->setGrossAmount($netAmount);
                }



                break;

            default:
                throw new UnsupportedException("Combination Mode [" . $price->getTaxEntryCombinationMode() . "] cannot be recalculated.");
                break;

        }

        return $price;
    }



    protected static function calculationFromGross(IPrice $price) {

        switch ($price->getTaxEntryCombinationMode()) {

            case TaxEntry::CALCULATION_MODE_COMBINE:

                $taxEntries = $price->getTaxEntries();

                if($taxEntries) {
                    $reverseTaxEntries = array_reverse($taxEntries);

                    $totalTaxAmount = 100;
                    foreach($taxEntries as $entry) {
                        $totalTaxAmount += $entry->getPercent();
                    }

                    $grossAmount = $price->getGrossAmount();

                    foreach($reverseTaxEntries as $entry) {
                        $amount = $grossAmount / $totalTaxAmount * $entry->getPercent();
                        $entry->setAmount( $amount );
                    }

                    $price->setNetAmount($grossAmount / $totalTaxAmount * 100);
                } else {
                    $price->setNetAmount($price->getGrossAmount());
                }

                break;

            case TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER:

                $taxEntries = $price->getTaxEntries();

                if($taxEntries) {
                    $reverseTaxEntries = array_reverse($taxEntries);

                    $grossAmount = $price->getGrossAmount();
                    $currentGrossAmount = $grossAmount;

                    foreach ($reverseTaxEntries as $entry) {
                        $amount = $currentGrossAmount / (100 + $entry->getPercent()) * $entry->getPercent();
                        $entry->setAmount($amount);
                        $currentGrossAmount = $currentGrossAmount - $amount;
                    }

                    $price->setNetAmount($currentGrossAmount);

                } else {
                    $price->setNetAmount($price->getGrossAmount());
                }

                break;

            default:
                throw new UnsupportedException("Combination Mode [" . $price->getTaxEntryCombinationMode() . "] cannot be recalculated.");
                break;

        }

        return $price;
    }


}