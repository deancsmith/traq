<?php
/*!
 * Traq
 * Copyright (C) 2009-2013 Traq.io
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

namespace Installer\Helpers\Upgrade;

// Traq 3.0 Ticket model
require dirname(dirname(__DIR__)) . "/models/ticket_upgrade.php";
use traq\models\TicketUpgrade;

use Installer\Helpers\Fixes;

/**
 * Traq 3.x upgrades.
 *
 * @author Jack P.
 * @since 3.3
 * @package Traq
 * @subpackage Installer
 */
class v3x extends Base
{
    /**
     * Available revisions.
     */
    protected static $revisions = array(
        // 3.0.x
        30006, 30007,

        // 3.1.x
        30100,

        // 3.2.x
        30200, 30201, 30202,

        // 3.3.x
        30300
    );

    /**
     * Traq v3.0.6
     */
    public static function v30006($db)
    {
        // Find a user with the group ID of the guest group and make it the anonymous user.
        if ($anon = $db->select()->from('users')->where('group_id', 3)->exec()->fetch()) {
            $anon = new User($anon, false);
            $anon->set(array(
                'username'   => 'Anonymous',
                'password'   => sha1(microtime() . rand(0, 200) . time() . rand(0, 200)) . microtime(),
                'name'       => 'Anonymous',
                'email'      => 'anonymous' . microtime() . '@' . $_SERVER['HTTP_HOST'],
                'group_id'   => 3,
                'locale'     => 'enUS',
                'options'    => '{"watch_created_tickets":null}',
                'login_hash' => sha1(microtime() . rand(0, 250) . time() . rand(0, 250) . microtime()),
            ));
        }
        // Create an anonymous user
        else {
            // Anonymous user
            $anon = new User(array(
                'username'   => 'Anonymous',
                'password'   => sha1(microtime() . rand(0, 200) . time() . rand(0, 200)) . microtime(),
                'name'       => 'Anonymous',
                'email'      => 'anonymous' . microtime() . '@' . $_SERVER['HTTP_HOST'],
                'group_id'   => 3,
                'locale'     => 'enUS',
                'options'    => '{"watch_created_tickets":null}',
                'login_hash' => sha1(microtime() . rand(0, 250) . time() . rand(0, 250) . microtime()),
            ));
        }

        // Save anonymous user
        $anon->save();

        // Create setting to save anonymous user ID
        $anon_id = new Setting(array(
            'setting' => 'anonymous_user_id',
            'value'   => $anon->id
        ));
        $anon_id->save();

        // Update tickets, timeline, history with a user ID of -1
        // to use the anonymous users ID.
        $db->update('tickets')->set(array('user_id' => $anon->id))->where('user_id', -1)->exec();
        $db->update('ticket_history')->set(array('user_id' => $anon->id))->where('user_id', -1)->exec();

        // Update timeline for anonymous user
        $db->update('timeline')->set(array('user_id' => $anon->id))->where('user_id', -1)->exec();
    }

    /**
     * Traq v3.0.7
     */
    public static function v30007($db)
    {
        foreach (TicketUpgrade::fetch_all() as $ticket) {
            $ticket->delete_voter('-1');
            $ticket->delete_voter(Setting::find('anonymous_user_id')->value);
            $ticket->quick_save();
        }

        // Fix severities table ID column to auto increment
        $db->query("ALTER TABLE `{$db->prefix}severities` CHANGE `id` `id` BIGINT(20) NOT NULL AUTO_INCREMENT");
    }

    /**
     * Traq v3.1.0
     */
    public static function v30100($db)
    {
        // Default value for project display order.
        $db->query("ALTER TABLE `{$db->prefix}projects` CHANGE `displayorder` `displayorder` BIGINT(20) NOT NULL DEFAULT '0'");

        // Add api_key column to users table
        $db->query(" ALTER TABLE `{$db->prefix}users` ADD COLUMN `api_key` VARCHAR(255) AFTER `login_hash`;");

        // Add setting for registration/email validation
        $db->query("
            INSERT INTO `{$db->prefix}settings` (`setting`, `value`)
            VALUES
              ('email_validation',0);
        ");

        // Add permissions for moving tickets
        $db->query("
            INSERT INTO `{$db->prefix}permissions` (`project_id`, `type`, `type_id`, `action`, `value`)
            VALUES
              (0,'usergroup',0,'move_tickets',0),
              (0,'role',0,'move_tickets',0),
              (0,'role',1,'move_tickets',1);
        ");

        // Add permissions for ticket properties
        $db->query("
            INSERT INTO `{$db->prefix}permissions` (`project_id`, `type`, `type_id`, `action`, `value`)
            VALUES
              (0,'usergroup',0,'ticket_properties_set_assigned_to',0),
              (0,'usergroup',0,'ticket_properties_set_milestone',0),
              (0,'usergroup',0,'ticket_properties_set_version',0),
              (0,'usergroup',0,'ticket_properties_set_component',0),
              (0,'usergroup',0,'ticket_properties_set_severity',0),
              (0,'usergroup',0,'ticket_properties_set_priority',0),
              (0,'usergroup',0,'ticket_properties_set_status',0),
              (0,'usergroup',0,'ticket_properties_change_type',0),
              (0,'usergroup',0,'ticket_properties_change_assigned_to',0),
              (0,'usergroup',0,'ticket_properties_change_milestone',0),
              (0,'usergroup',0,'ticket_properties_change_version',0),
              (0,'usergroup',0,'ticket_properties_change_component',0),
              (0,'usergroup',0,'ticket_properties_change_severity',0),
              (0,'usergroup',0,'ticket_properties_change_priority',0),
              (0,'usergroup',0,'ticket_properties_change_status',0),
              (0,'usergroup',0,'ticket_properties_change_summary',0),
              (0,'role',0,'ticket_properties_set_assigned_to',1),
              (0,'role',0,'ticket_properties_set_milestone',1),
              (0,'role',0,'ticket_properties_set_version',1),
              (0,'role',0,'ticket_properties_set_component',1),
              (0,'role',0,'ticket_properties_set_severity',1),
              (0,'role',0,'ticket_properties_set_priority',1),
              (0,'role',0,'ticket_properties_set_status',1),
              (0,'role',0,'ticket_properties_change_type',1),
              (0,'role',0,'ticket_properties_change_assigned_to',1),
              (0,'role',0,'ticket_properties_change_milestone',1),
              (0,'role',0,'ticket_properties_change_version',1),
              (0,'role',0,'ticket_properties_change_component',1),
              (0,'role',0,'ticket_properties_change_severity',1),
              (0,'role',0,'ticket_properties_change_priority',1),
              (0,'role',0,'ticket_properties_change_status',1),
              (0,'role',0,'ticket_properties_change_summary',1);
        ");
    }

    /**
     * Traq v3.2.0
     */
    public static function v30200($db)
    {
        // Add tasks column to tickets table
        $db->query("ALTER TABLE `" . $db->prefix . "tickets` ADD COLUMN `tasks` longtext AFTER `votes`;");

        // Add new permissions
        $db->query("
            INSERT INTO `" . $db->prefix . "permissions` (`project_id`, `type`, `type_id`, `action`, `value`)
            VALUES
              (0,'usergroup',0,'delete_timeline_events',0),
              (0,'usergroup',0,'perform_mass_actions',0),
              (0,'usergroup',0,'ticket_properties_set_tasks',0),
              (0,'usergroup',0,'ticket_properties_change_tasks',0),
              (0,'usergroup',0,'ticket_properties_complete_tasks',0),
              (0,'role',0,'delete_timeline_events',0),
              (0,'role',1,'delete_timeline_events',1),
              (0,'role',0,'perform_mass_actions',0),
              (0,'role',1,'perform_mass_actions',1),
              (0,'role',0,'ticket_properties_set_tasks',1),
              (0,'role',0,'ticket_properties_change_tasks',1),
              (0,'role',0,'ticket_properties_complete_tasks',1);
          ");
    }

    /**
     * Traq v3.2.1
     */
    public static function v30201($db)
    {
        // Add default ticket type ID column to projects table
        $db->query("ALTER TABLE `". $db->prefix . "projects` ADD COLUMN `default_ticket_type_id` int AFTER `enable_wiki`;");
    }

    /**
     * Traq v3.2.2
     */
    public static function v30202($db)
    {
        // Add assignable column to project role table.
        $db->query("ALTER TABLE `{$db->prefix}project_roles` ADD COLUMN `assignable` TINYINT(1) NOT NULL DEFAULT '1' AFTER `name`;");
    }

    /**
     * Traq v3.3.0
     */
    public static function v30300($db)
    {
        // Custom field values table
        $db->query("
            CREATE TABLE `". $db->prefix . "custom_field_values` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `custom_field_id` bigint(20) NOT NULL,
              `ticket_id` bigint(20) NOT NULL,
              `value` text,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        // Loop over tickets and place custom field values
        // into the new table.
        foreach (TicketUpgrade::fetch_all() as $ticket) {
            foreach ($ticket->extra['custom_fields'] as $field_id => $value) {
                $field = new CustomFieldValue(array(
                    'custom_field_id' => $field_id,
                    'ticket_id'       => $ticket->id,
                    'value'           => $value
                ));

                $field->save();
            }

            $ticket->remove_custom_fields();
            $ticket->save();
        }

        // Add default value for milestone_id field in the tickets table
        $db->query("ALTER TABLE `{$db->prefix}tickets` CHANGE `milestone_id` `milestone_id` BIGINT(20) NOT NULL DEFAULT '0';");

        // Site name/URL and ticket history sorting setting rows
        $db->query("
          INSERT INTO `{$db->prefix}settings` (`setting`, `value`)
          VALUES
            ('site_name', ''),
            ('site_url', ''),
            ('ticket_history_sorting', 'oldest_first');
        ");

        // Add custom fields slug field.
        $db->query("ALTER TABLE `{$db->prefix}custom_fields` ADD `slug` VARCHAR(255) NOT NULL AFTER `name`;");

        // Update current custom fields and create the slug.
        foreach ($db->query("SELECT `id`, `name` FROM `{$db->prefix}custom_fields`")->fetchAll(\PDO::FETCH_ASSOC) as $field) {
            $slug = create_slug($field['name']);
            $db->query("UPDATE `{$db->prefix}custom_fields` SET `slug` = '{$slug}' WHERE `id` = {$field['id']}");
        }

        // Default ticket sorting
        $db->query("ALTER TABLE `{$db->prefix}projects` ADD `default_ticket_sorting` VARCHAR(255) NOT NULL DEFAULT 'priority.asc' AFTER `default_ticket_type_id`;");

        Fixes::deletedUsers();
    }
}