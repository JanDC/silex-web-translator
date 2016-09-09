<?php

namespace WebTranslator\Utility;

use Silex\Translator;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\TranslatorInterface;

class TranslationHelper
{
    public static function getRealCatalogueSize($primaryLocale, Translator $translator)
    {
        $primaryCatalogue = $translator->getCatalogue($primaryLocale);
        $size = 0;
        foreach ($primaryCatalogue->all() AS $domain => $translations) {
            $size += sizeof($translations);
        }
        return $size;
    }

    public static function getTotalUntranslated($primaryLocale, Translator $translator,array $locales)
    {
        $untranslated = 0;

        $primaryCatalogue = $translator->getCatalogue($primaryLocale)->all();

        // iterate through the primary catalogue as our source of truth
        foreach ($locales AS $locale) {

            if ($locale != $primaryLocale) {
                $fallbackCatalogue = $translator->getCatalogue($locale);

                // loop through the primary catalogue and check to see if it's present in the fallback
                foreach ($primaryCatalogue AS $domain => $translations) {
                    foreach ($translations AS $translationKey => $translationValue) {
                        if (!$fallbackCatalogue->has($translationKey, $domain)) {
                            $untranslated++;
                        }
                    }
                }
            }
        }

        return $untranslated;
    }

    public static function compareTotalTranslations(TranslatorInterface $translator, $locale1, $locale2)
    {

        $catalogue1 = $translator->getCatalogue($locale1)->all();
        $catalogue2 = $translator->getCatalogue($locale2)->all();

        $size1 = $size2 = 0;

        foreach ($catalogue1 AS $domain => $translations) {
            $size1 += sizeof($translations);
        }

        foreach ($catalogue2 AS $domain => $translations) {
            $size2 += sizeof($translations);
        }

        return $size1 - $size2;
    }
}