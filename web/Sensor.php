<?php
/*
 * Copyright (C) 2013 OHRI 
 * This file is part of OWTG.
 * 
 * OWTG is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * OWTG is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with OWTG.  If not, see <http://www.gnu.org/licenses/>.
*/

class Sensor
{
    public $address = '';
    public $alias = '';
    public $timestamp = 0;
    public $temperature = '';
    public $minAlarm = 0;
    public $maxAlarm = 0;
    public $online = False;
    public $graph = False;
}  
?>
