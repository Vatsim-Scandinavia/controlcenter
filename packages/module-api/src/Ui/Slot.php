<?php

namespace ControlCenter\ModuleApi\Ui;

/**
 * The stable set of named UI slots a module may render into.
 *
 * Each slot is authorized per-item and rendered with error isolation: a slot
 * that throws is reported and skipped, never breaking the surrounding page.
 */
final class Slot
{
    public const string DashboardWidgets = 'dashboard.widgets';

    public const string UserPanels = 'user.panels';

    public const string TrainingPanels = 'training.panels';

    public const string TrainingActions = 'training.actions';

    public const string AdminSettings = 'admin.settings';

    public const string UserSettings = 'user.settings';

    public const string Head = 'head';

    public const string Scripts = 'scripts';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::DashboardWidgets,
            self::UserPanels,
            self::TrainingPanels,
            self::TrainingActions,
            self::AdminSettings,
            self::UserSettings,
            self::Head,
            self::Scripts,
        ];
    }
}
