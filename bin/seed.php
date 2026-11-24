<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/database/seeders/DatabaseSeeder.php';

use Database\Seeders\DatabaseSeeder;

DatabaseSeeder::run();

echo "DarsYar database seeded successfully.\n";
echo "Demo accounts (password: School@2026):\n";
echo "  - superadmin (Super Admin)\n";
echo "  - director (School Director)\n";
echo "  - viceprincipal (Vice Principal)\n";
echo "  - counselor (School Counselor)\n";
echo "  - teacher1 (Teacher)\n";
echo "  - student1, student2, student3, student4 (Students)\n";
echo "  - parent1 (Parent linked to student1 and student4)\n";
