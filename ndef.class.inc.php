<?php
/**
 *  NDEF Helper Class
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
 */

namespace ndef;
class ndefData {
    public $TNF_EMPTY = 0x0;
    public $TNF_WELL_KNOWN = 0x01;
    public $TNF_MIME_MEDIA = 0x02;
    public $TNF_ABSOLUTE_URI = 0x03;
    public $TNF_EXTERNAL_TYPE = 0x04;
    public $TNF_UNKNOWN = 0x05;
    public $TNF_UNCHANGED = 0x06;
    public $TNF_RESERVED = 0x07;

    public $RTD_TEXT = [0x54];
    public $RTD_URI = [0x55];
    public $RTD_URI_TYPES = [null, 'http://www', 'https://www.', 'http://', 'https://',
        'tel:', 'mailto:', 'ftp://anonymous:anonymous@', 'ftp://ftp.',
        'ftps://', 'sftp://', 'smb://', 'nfs://', 'ftp://', 'dav://',
        'news:', 'telnet://', 'imap:', 'rtsp://', 'urn:', 'pop:', 'sip:',
        'sips:', 'tftp:', 'btspp://', 'btl2cap://', 'btgoep://', 'tcpobex://',
        'irdaobex://', 'file://', 'urn:epc:id:', 'urn:epc:tag:', 'urn:epc:pat:',
        'urn:epc:raw:', 'urn:epc:', 'urn:nfc:'];
    public $RTD_SMART_POSTER = [0x53, 0x70];
    public $RTD_ALTERNATIVE_CARRIER = [0x61, 0x63];
    public $RTD_HANDOVER_CARRIER = [0x48, 0x63];
    public $RTD_HANDOVER_REQUEST = [0x48, 0x72];
    public $RTD_HANDOVER_SELECT = [0x48, 0x73];

    public $TLV_NDEF = 0x03;
    public $TLV_TERMINATOR = 0xFE;

}

class ndefTools extends ndefData {
    public function stringToBytes($str)    {
        return unpack('C*',$str);
    }

    public function bytesToString($bytes)  {
        $returnString   =   '';
        foreach ($bytes as $byte)   $returnString   .=  ord($byte);
        return $returnString;
    }

    public function hexStringToBytes($str) {
        $returnArray      =   array();
        $str              =   str_split($str,2);
        foreach ($str as $b)    $returnArray[]  =   hexdec($b);
        return $returnArray;
    }

    public function bytesToHexString($bytes)   {
        $returnString   =   '';
        foreach ($bytes as $byte)   $returnString   .=  sprintf("%02X",$byte);
        return $returnString;
    }

}


class ndef    extends ndefTools
{
    private $encodedMessage;
    /**
     * Creates a JSON representation of a NDEF Record.
     *
     * @tnf 3-bit TNF (Type Name Format) - use one of the TNF_* constants
     * @type byte array, containing zero to 255 bytes, must not be null
     * @id byte array, containing zero to 255 bytes, must not be null
     * @payload byte array, containing zero to (2 ** 32 - 1) bytes, must not be null
     *
     * @returns JSON representation of a NDEF record
     *
     * @see Ndef.textRecord, Ndef.uriRecord and Ndef.mimeMediaRecord for examples
     */
    public function record($tnf = null,
                            $type = array(),
                            $id = array(),
                            $payload = array())
    {

        if (!is_array($type) && count($type) != 0) {
            $type = $this->stringToBytes($type);
        }
        if (!is_array($id) && count($id) != 0) {
            $id = $this->stringToBytes($id);
        }
        if (!is_array($payload) && count($payload) != 0) {
            $payload = $this->stringToBytes($payload);
        }

        $returnArray['tnf']     =   $tnf;
        $returnArray['type']    =   $type;
        $returnArray['id']      =   $id;
        $returnArray['payload'] =   $payload;

        return $returnArray;

    }

    /**
     * Helper that creates an NDEF record containing plain text.
     *
     * @text String of text to encode
     * @languageCode ISO/IANA language code. Examples: “fi”, “en-US”, “fr- CA”, “jp”. (optional)
     * @id byte[] (optional)
     */
    public function textRecord($text = null,
                                $languageCode   =   "en",
                                $id             =   array())    {

        $payload    =   array();

        $payload[]  =   strlen($languageCode);
        $payload    =   array_merge($payload, $this->stringToBytes($text));

        $record     =   new record();
        $record->setTNF($this->TNF_WELL_KNOWN)
               ->setType($this->RTD_TEXT)
               ->setId($id)
               ->setPayload($payload);

        return $record;
    }

    /**
     * Helper that creates a NDEF record containing a URI.
     *
     * @uri String
     * @id byte[] (optional)
     */
    public function uriRecord (    $uri,
                                    $id = array())  {
        $payload    =   array();
        $found      =   FALSE;
        foreach ($this->RTD_URI_TYPES as $val => $type) {
            if (strpos($uri,$type) !== false)   {
                $payload[]  =   dechex($val);

                $uri        =   str_replace($type,'',$uri);
                $found      =   TRUE;
                break;
            }
        }

        if ($found === FALSE)   $payload[]  =   0x0;

        $payload         =    array_merge($payload,$this->stringToBytes($uri));

        $record     =   new record();
        $record->setTNF($this->TNF_WELL_KNOWN)
            ->setType($this->RTD_URI)
            ->setId($id)
            ->setPayload($payload);

        return $record;
    }

    public function unknownRecord ($hexData,
                                    $id = array())  {

        //We're accepting the data as a string of hex.
        //We need to change this into an array of byte values
        $payload    =   $this->hexStringToBytes($hexData);

        $record     =   new record();
        $record->setTNF($this->TNF_UNKNOWN)
            ->setType()
            ->setId($id)
            ->setPayload($payload);

        return $record;

    }

    /**
     * Helper that creates a NDEF record containing an absolute URI.
     *
     * @text String
     * @id byte[] (optional)
     */
    public function absoluteUriRecord( $text,
                                        $id =   array()) {

        $record     =   new record();
        $record->setTNF($this->TNF_ABSOLUTE_URI)
            ->setType($this->hexStringToBytes($text))
            ->setId($id)
            ->setPayload(array());

        return $record;
    }

    /**
     * Helper that creates a NDEF record containing an mimeMediaRecord.
     *
     * @mimeType String
     * @payload byte[]
     * @id byte[] (optional)
     */
    public function mimeMediaRecord(   $mimeType,
                                        $payload    =   array(),
                                        $id)    {
        $record     =   new record();
        $record->setTNF($this->TNF_MIME_MEDIA)
            ->setType($this->stringToBytes($mimeType))
            ->setId($id)
            ->setPayload($payload);

        return $record;
    }

    /**
     * Helper that creates an NDEF record containing an Smart Poster.
     *
     * @ndefRecords array of NDEF Records
     * @id byte[] (optional)
     */
    public function smartPoster(   Array $ndefRecords    =   array(),
                                   $id) {

        $payload    =   array();
        if (count($payload !== 0))  {
            if ($ndefRecords instanceof record) {
                $payload    =   $this->encodeMessage($ndefRecords);
            } else {
                throw new Exception ("Expected: Array of records");
            }
        } else {
            throw new Exception ("Expected: Array of records");
        }

        $record     =   new record();
        $record->setTNF($this->TNF_WELL_KNOWN)
            ->setType($this->RTD_SMART_POSTER)
            ->setId($id)
            ->setPayload($payload);

        return $record;
    }

    public function emptyRecord()  {
        $record     =   new record();
        $record->setTNF($this->TNF_EMPTY)
            ->setType(array())
            ->setId(array())
            ->setPayload(array());

        return $record;
    }

    public function getEncodedMessage() {
        return $this->encodedMessage;
    }

    public function encodeMessage(Array $ndefRecords)  {
        $message    =   new message();
        $message->setNDEFRecords($ndefRecords);
        $message->encodeMessage();
        $this->encodedMessage = $message->getEncodedMessage();
        return true;
    }

}

class record    extends ndefTools{
    private $tnf;
    private $type;
    private $id         =   array();
    private $payload    =   array();

    public function getTNF()    {
        return $this->tnf;
    }
    public function getType()    {
        return $this->type;
    }
    public function getId()    {
        return $this->id;
    }
    public function getPayload()    {
        return $this->payload;
    }

    public function getPayloadSize()    {
        return count($this->payload);
    }

    public function getTypeLength() {
        return count($this->type);
    }

    public function setType($type   =   null)    {
        if (!is_array($type) && count($type) != 0) {
            $this->type = $this->stringToBytes($type);
        } else {
            if (!is_null($type))    {
                $this->type =   $type;
            } else  {
                $this->type =   array();
            }

        }
        return $this;
    }
    public function setTNF($tnf)    {
        $this->tnf =   $tnf;
        return $this;
    }
    public function setId(Array $id)    {
        $this->id =   $id;
        return $this;
    }
    public function setPayload(Array $payload)    {
        $this->payload =   $payload;
        return $this;
    }

}

class message   extends ndefTools   {
    private $encoded        =   array();
    private $ndefRecords    =   array();
    private $tnf_byte;
    private $type_length;
    private $payload_length;
    private $id_length;
    private $i;

    public function setNDEFRecords(array $ndefRecords)    {
        if (!is_array($ndefRecords) || count($ndefRecords) == 0 )   {
            throw new Exception("Expects: Array of NDEF Records");
        }

        //Check to see that we actually have an array of records
        foreach ($ndefRecords as $record)   {
            if (!$record instanceof record) throw new Exception("Expects: Array elements must be of Record Type");
        }

        $this->ndefRecords  =   $ndefRecords;
        return true;
    }

    public function encodeMessage() {
        if (count($this->ndefRecords) == 0) throw new Exception("No records to encode!");
        foreach ($this->ndefRecords as $key => $record) {
            $tnf    =   new tnf();
            $tnf->setTNF($record->getTNF())
                ->setIndex($key)
                ->setRecord($record)
                ->setRecords($this->ndefRecords);
            $this->pushData($tnf->buildTNF());
        }
    }

    public function decodeMessage() {
        //TO DO
    }
        public function getEncodedMessage()    {
        return $this->encoded;
    }

    private function pushData($data) {
        //array_push($this->encoded,$data);
        $this->encoded  =   array_merge($this->encoded, $data);
        return true;
    }


}

class tag extends ndefTools {
    private $encoded;

    public function addEncoded($encoded)    {
        $this->encoded  =   $encoded;
        return $this;
    }

    public function encodeTag() {
        array_unshift($this->encoded, count($this->encoded));
        array_unshift($this->encoded, $this->TLV_NDEF);
        array_push($this->encoded, $this->TLV_TERMINATOR);
        return true;
    }

    public function getEncodedTag() {
        return $this->encoded;
    }

}

class tnf extends ndefTools {
    private $mb;
    private $me;
    private $cf     =   FALSE;
    private $sr;
    private $il;
    private $tnf;
    private $record =   FALSE;
    private $records=   FALSE;
    private $recordsLength;
    private $index  =   0;
    private $result =   array();

    public function setIndex($index)    {
        $this->index      =     $index;
        $this->record     =     $this->records[$index];
        return $this;
    }

    public function setRecords(array $records)   {
        //Check to see that we actually have an array of records
        foreach ($records as $record)   {
            if (!$record instanceof record) throw new Exception("Expects: Array elements must be of Record Type");
        }

        $this->records =   $records;
        $this->recordsLength    =   count($this->records);
        return $this;
    }

    public function setRecord(record $record)   {
        $this->record   =   $record;
        return $this;
    }

    public function setTNF($tnf)    {
        $this->tnf      =   $tnf;
        return $this;
    }

    public function getRecordsLength()   {
        return $this->recordsLength;
    }


    private function getTNF()    {
        if (!$this->record) throw new Exception("Record must be set and of type record");

        $this->mb   =   ($this->index === 0);
        $this->me   =   ($this->index === ($this->getRecordsLength() - 1));
        $this->sr   =   ($this->record->getPayloadSize() < 0xFF);
        $this->il   =   (count($this->record->getId()) > 0);

        if ($this->mb)  {   $this->tnf  =   $this->tnf | 0x80;  }
        if ($this->me)  {   $this->tnf  =   $this->tnf | 0x40;  }
        if ($this->cf)  {   $this->tnf  =   $this->tnf | 0x20;  }
        if ($this->sr)  {   $this->tnf  =   $this->tnf | 0x10;  }
        if ($this->il)  {   $this->tnf  =   $this->tnf | 0x8;   }

        return $this->tnf;

    }

    public function buildTNF()    {
        $this->result[] =   $this->getTNF();

        $this->result[] =   $this->record->getTypeLength();

        if ($this->sr)  {
            $this->result[]    =    $this->record->getPayloadSize();
        } else {
            $this->result[]    =    $this->record->getPayloadSize() >> 24;
            $this->result[]    =    $this->record->getPayloadSize() >> 16;
            $this->result[]    =    $this->record->getPayloadSize() >> 8;
            $this->result[]    =    $this->record->getPayloadSize() & 0xFF;
        }
        
        if ($this->il)  {
            $this->result[]     =   count($this->record->getId());
        }

        $this->result          =   array_merge($this->result, $this->record->getType());

        if ($this->il)  {
            $this->result[]     =   $this->record->getId();
        }

        $this->result          =   array_merge($this->result, $this->record->getPayload());

        return $this->result;

    }




}