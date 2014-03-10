PHP Class for creating / managing multi-record NDEF records for NFC tags. Finally.

====
    NDEF Helper Class
    Copyright (C) 2013 Simon YORKSTON

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
====

PHP class to assist in the creation of NDEF records for NFC tags.
Class permits multiple records, and multiple record types.

Example.php holds a working example:

    <?
  
    /*
    * Creates a tag with two NDEF records - one URL, and one freeform.
    *
    */

    $records    =   array();
    $ndef       =   new \ndef\ndef();
    $tag        =   new \ndef\tag();
    $records[]  =   $ndef->uriRecord('http://ninjito.com');
    $records[]  =   $ndef->unknownRecord("000000000000000000000000000000000000AC77003C082B2E39906704E23E3EDC2355CF559CEBB8FEE99F68FD913CD0A828B310F5102F33FD4EAF4F095BB2AD5129");
    $ndef->encodeMessage($records);
    $tag->addEncoded($ndef->getEncodedMessage());
    $tag->encodeTag();
  
    var_dump($tag);
