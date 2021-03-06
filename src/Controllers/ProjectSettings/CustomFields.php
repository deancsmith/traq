<?php
/*!
 * Traq
 * Copyright (C) 2009-2016 Jack P.
 * Copyright (C) 2012-2016 Traq.io
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

namespace Traq\Controllers\ProjectSettings;

use Avalon\Http\Request;
use Traq\Models\CustomField;

/**
 * Custom fields controller.
 *
 * @author Jack P.
 * @since 3.0.0
 * @package Traq\Controllers\ProjectSettings
 */
class CustomFields extends AppController
{
    public function __construct()
    {
        parent::__construct();
        $this->addCrumb($this->translate('custom_fields'), $this->generateUrl('project_settings_custom_fields'));

        $this->before(['edit', 'save', 'destroy'], function () {
            $this->object = CustomField::find(Request::$properties->get('id'));

            if (!$this->object || $this->object->project_id != $this->currentProject['id']) {
                return $this->show404();
            }
        });
    }

    /**
     * Custom field listing.
     */
    public function indexAction()
    {
        $fields = CustomField::select()
            ->where('project_id = ?')
            ->orderBy('name', 'ASC')
            ->setParameter(0, $this->currentProject['id'])
            ->fetchAll();

        return $this->render('project_settings/custom_fields/index.phtml', [
            'fields' => $fields
        ]);
    }

    /**
     * New field form.
     */
    public function newAction()
    {
        $view = $this->isModal ? 'new.overlay' : 'new';

        return $this->render("project_settings/custom_fields/{$view}.phtml", [
            'field' => new CustomField
        ]);
    }

    /**
     * Create custom field.
     */
    public function createAction()
    {
        $field = new CustomField($this->fieldParams());

        return $this->respondTo(function ($format) use ($field) {
            if ($field->save()) {
                if ($format === 'html') {
                    return $this->redirectTo('project_settings_custom_fields');
                } elseif ($format === 'json') {
                    return $this->jsonResponse($field);
                }
            } else {
                if ($format === 'html') {
                    return $this->render('project_settings/custom_fields/new.phtml', [
                        'field' => $field
                    ]);
                } elseif ($format === 'json') {
                    return $this->jsonResponse(
                        [
                            'errors' => $field->errors(),
                            'field' => $field
                        ],
                        422
                    );
                }
            }
        });
    }

    /**
     * Edit custom field.
     *
     * @param integer $id
     */
    public function editAction($id)
    {
        $field = CustomField::select()
            ->where('id = ?')
            ->andWhere('project_id = ?')
            ->setParameter(0, $id)
            ->setParameter(1, $this->currentProject['id'])
            ->fetch();

        if (!$field) {
            return $this->show404();
        }

        $view = $this->isModal ? 'edit.overlay' : 'edit';

        return $this->render("project_settings/custom_fields/{$view}.phtml", [
            'field' => $field
        ]);
    }

    /**
     * Edit custom field.
     *
     * @param integer $id
     */
    public function saveAction($id)
    {
        $field = CustomField::select()
            ->where('id = ?')
            ->andWhere('project_id = ?')
            ->setParameter(0, $id)
            ->setParameter(1, $this->currentProject['id'])
            ->fetch();

        $field->set($this->fieldParams());

        return $this->respondTo(function ($format) use ($field) {
            if ($field->save()) {
                if ($format === 'html') {
                    return $this->redirectTo('project_settings_custom_fields');
                } elseif ($format === 'json') {
                    return $this->jsonResponse($field);
                }
            } else {
                if ($format === 'html') {
                    return $this->render('project_settings/custom_fields/edit.phtml', [
                        'field' => $field
                    ]);
                } elseif ($format === 'json') {
                    return $this->jsonResponse(
                        [
                            'errors' => $field->errors(),
                            'field' => $field
                        ],
                        422
                    );
                }
            }
        });
    }

    /**
     * Delete field.
     */
    public function destroyAction($id)
    {
        $field = CustomField::select()
            ->where('id = ?')
            ->andWhere('project_id = ?')
            ->setParameter(0, $id)
            ->setParameter(1, $this->currentProject['id'])
            ->fetch();

        $field->delete();

        return $this->respondTo(function ($format) use ($field) {
            if ($format == "html") {
                return $this->redirectTo('project_settings_custom_fields');
            } elseif ($format == "json") {
                return $this->jsonResponse([
                    'deleted' => true,
                    'field' => $field->toArray()
                ]);
            }
        });
    }

    /**
     * @return array
     */
    protected function fieldParams()
    {
        return $this->removeNullValues([
            'name' => Request::$post['name'],
            'slug' => Request::$post['slug'],
            'type' => Request::$post->get('type', 0),
            'min_length' => Request::$post['min_length'],
            'max_length' => Request::$post['max_length'],
            'regex' => Request::$post['regex'],
            'default_value' => Request::$post['default_value'],
            'field_values' => Request::$post['field_values'],
            'multiple' => Request::$post['multiple'],
            'is_required' => Request::$post['is_required'],
            'ticket_type_ids' => Request::$post['ticket_type_ids'],
            'project_id' => $this->currentProject['id']
        ]);
    }
}
