<?php

return [
    'default_responsible_id' => (int) env('LEADHUB_DEFAULT_RESPONSIBLE_ID') ?: null,
    'manager_id' => (int) env('LEADHUB_MANAGER_ID') ?: null,
    'reminder_after_minutes' => (int) env('LEADHUB_REMINDER_MINUTES', 15),
    'escalation_after_minutes' => (int) env('LEADHUB_ESCALATION_MINUTES', 60),
];
