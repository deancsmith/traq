<?php
/*!
 * Traq
 * Copyright (C) 2009-2015 Jack Polgar
 * Copyright (C) 2012-2015 Traq.io
 * https://github.com/nirix
 * https://traq.io
 *
 * This file is part of Traq.
 *
 * Traq is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 only.
 *
 * Traq is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Traq. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Traq\Installer\Controllers;

use Avalon\Http\Controller;

/**
 * @author Jack P.
 * @since 4.0.0
 */
class AppController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->set("installStep", function($routeName) {
            return $this->request->basePath("index.php") . $this->generateUrl($routeName);
        });

        $this->set("drivers", [
            'pdo_mysql'  => "MySQL",
            'pdo_pgsql'  => "PostgreSQL",
            'pdo_sqlite' => "SQLite",
            // 'pdo_sqlsrv' => "SQL Server",
            // 'pdo_oci'    => "Oracle"
        ]);
    }

    /**
     * Set page title.
     *
     * @param string $title
     */
    protected function title($title)
    {
        $this->set("stepTitle", $title);
    }
}
