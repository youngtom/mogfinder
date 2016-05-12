<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
	
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class
        Commands\ItemsImport::class,
        Commands\GameItemsImport::class,
        Commands\UpdateBitmasks::class,
        Commands\ImportUserData::class,
        Commands\ItemSourceConversion::class,
        Commands\ItemDisplayUpdate::class,
        Commands\ImportWowdbSources::class,
        Commands\ListDuplicateItems::class,
        Commands\RemoveDuplicateItemDisplays::class,
        Commands\RemoveDuplicateItemSources::class,
        Commands\UpdateTransmoggableItemDisplays::class,
        Commands\UpdateItemDisplayRenders::class,
        Commands\CreateUser::class,
        Commands\UpdateItemFactionInfo::class,
        Commands\ImportIconImages::class,
        Commands\UpdateItemData::class,
        Commands\SetupClassItemSubtypeStats::class,
        Commands\ImportDynamicQuests::class,
        Commands\ResetUserData::class,
        Commands\wowhead\ImportUntransmoggableItems::class,
        Commands\wowhead\ImportQuestItemData::class,
        Commands\ResetUserQuestData::class,
        Commands\UpdateUserQuestData::class,
        Commands\AuctionDataUpdate::class,
        Commands\UpdateItemDisplayRestrictions::class,
        Commands\UpdateUserItemDisplays::class,
        Commands\ImportBnetZoneData::class,
        Commands\ItemSourceDataImportHelper::class,
        Commands\ImportQuestZoneData::class,
        Commands\wowhead\UpdateZoneDropSources::class,
        Commands\wowhead\ImportMissingItemSources::class,
        Commands\wowhead\ImportMissingSourceData::class,
        Commands\wowhead\ImportSourceData::class,
        Commands\wowhead\UpdateItemsLockedRaces::class,
        Commands\RefreshItemDisplayZones::class,
        Commands\UpdateItemSourceRelatedItems::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('auctions:update')->everyTenMinutes();
    }
}
