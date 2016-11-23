<?php

/**
 * Adds the ability to used Versioned items in a ComplexTableField and Popup
 * @author Michael Mitchell <michael@sunnysideup.co.nz>
 */

class VersionedComplexTableField extends ComplexTableField
{
    public $itemClass = 'VersionedComplexTableField_Item';

    public static $url_handlers = array(
        'item/$ID/version/$Version' => 'handleItem',
        '$Action!' => '$Action',
    );

    public function handleItem($request)
    {
        return new VersionedComplexTableField_ItemRequest($this, $request->param('ID'), $request->param('Version'));
    }
}

class VersionedComplexTableField_ItemRequest extends ComplexTableField_ItemRequest
{
    public function __construct($ctf, $itemID, $versionID)
    {
        $this->versionID = $versionID;
        parent::__construct($ctf, $itemID);
    }

    public function dataObj()
    {
        if (is_numeric($this->itemID) && is_numeric($this->versionID)) {
            return Versioned::get_version(ClassInfo::baseDataClass(Object::getCustomClass($this->ctf->sourceClass())), $this->itemID, $this->versionID);
        }
    }
}

class VersionedComplexTableField_Item extends ComplexTableField_Item
{
    public function Link()
    {
        return $this->parent->Link() . '/item/' . $this->item->ID.'/version/'.$this->item->Version;
    }
}
