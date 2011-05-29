<?php
// Functions and logic for GEDCOM "NAME" codes
//
// webtrees: Web based Family History software
// Copyright (C) 2011 webtrees development team.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// $Id$

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

class WT_Gedcom_Code_Name {

	private static $TYPES=array('adopted', 'aka', 'birth', 'change', 'immigrant', 'maiden', 'married', 'religious');

	// Translate a code, for an (optional) record
	public static function getValue($type, $record=null) {
		if ($record instanceof WT_Person) {
			$sex=$record->getSex();
		} else {
			$sex='U';
		}

		switch ($type) {
		case 'adopted':
			switch ($sex) {
			case 'M': return WT_I18N::translate_c('MALE',   'adopted name');
			case 'F': return WT_I18N::translate_c('FEMALE', 'adopted name');
			default:  return WT_I18N::translate  (			'adopted name');
			}
		case 'aka':
			switch ($sex) {
			case 'M': return WT_I18N::translate_c('MALE',   'also known as');
			case 'F': return WT_I18N::translate_c('FEMALE', 'also known as');
			default:  return WT_I18N::translate  (			'also known as');
			}
		case 'birth':
			switch ($sex) {
			case 'M': return WT_I18N::translate_c('MALE',   'birth name');
			case 'F': return WT_I18N::translate_c('FEMALE', 'birth name');
			default:  return WT_I18N::translate  (			'birth name');
			}
		case 'change':
			switch ($sex) {
			case 'M': return WT_I18N::translate_c('MALE',   'change of name');
			case 'F': return WT_I18N::translate_c('FEMALE', 'change of name');
			default:  return WT_I18N::translate  (			'change of name');
			}
		case 'immigrant':
			switch ($sex) {
			case 'M': return WT_I18N::translate_c('MALE',   'immigration name');
			case 'F': return WT_I18N::translate_c('FEMALE', 'immigration name');
			default:  return WT_I18N::translate  (			'immigration name');
			}
		case 'maiden':
			switch ($sex) {
			case 'M': return WT_I18N::translate_c('MALE',   'maiden name');
			case 'F': return WT_I18N::translate_c('FEMALE', 'maiden name');
			default:  return WT_I18N::translate  (			'maiden name');
			}
		case 'married':
			switch ($sex) {
			case 'M': return WT_I18N::translate_c('MALE',   'married name');
			case 'F': return WT_I18N::translate_c('FEMALE', 'married name');
			default:  return WT_I18N::translate  (			'married name');
			}
		case 'religious':
			switch ($sex) {
			case 'M': return WT_I18N::translate_c('MALE',   'religious name');
			case 'F': return WT_I18N::translate_c('FEMALE', 'religious name');
			default:  return WT_I18N::translate  (			'religious name');
			}
		default:
			return $type;
		}
	}

	// A list of all possible values for NAME types
	public static function getValues($record=null) {
		$values=array();
		foreach (self::$TYPES as $type) {
			$values[$type]=self::getValue($type, $record);
		}
		uasort($values, 'utf8_strcasecmp');
		return $values;
	}
}
