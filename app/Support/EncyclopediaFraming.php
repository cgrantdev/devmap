<?php

namespace App\Support;

/**
 * Encyclopedia compounds with corrected class language.
 * Profiles supply title, meta, H1, and body copy for empty stubs.
 */
class EncyclopediaFraming
{
    /** @var list<class-string> */
    private const PROFILES = [
        SluPp332Profile::class,
        OrforglipronProfile::class,
        BacteriostaticWaterProfile::class,
        HcgProfile::class,
        NadPlusProfile::class,
    ];

    public static function match(?string $slug, ?string $name = null): ?FramingProfile
    {
        foreach (self::PROFILES as $profile) {
            if ($profile::matches($slug, $name)) {
                return FramingProfile::fromClass($profile);
            }
        }

        $definition = CatalogFraming::find($slug, $name);

        return $definition ? FramingProfile::fromDefinition($definition) : null;
    }

    /**
     * @return list<class-string>
     */
    public static function all(): array
    {
        return self::PROFILES;
    }
}
