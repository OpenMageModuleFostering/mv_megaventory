<?php
  
class Mv_Megaventory_Model_Notification_Feed extends Mage_AdminNotification_Model_Feed
{
    const XML_FREQUENCY_PATH    = 'megaventory/feed/check_frequency';
    const XML_LAST_UPDATE_PATH  = 'megaventory/feed/last_update';
    
    const URL_NEWS        = 'http://www.megaventory.com/Webservices/MagentoFeed.xml';

	
	public static function check()
	{
		return Mage::getModel('megaventory/notification_feed')->checkUpdate();
	}
	
    public function checkUpdate()
    {
        if (($this->getFrequency() + $this->getLastUpdate()) > time()) {
            return $this;
        }
        
        $this->setLastUpdate();
        
        if (!extension_loaded('curl')) {
            return $this;
        }

        // load all new and relevant updates into inbox
        $feedData   = array();
        $feedXml = $this->getFeedData();
        
        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
            foreach ($feedXml->channel->item as $item) {
                $date = $this->getDate((string)$item->pubDate);

               	$feedData[] = array(
                    'severity'      => 3,
                    'date_added'    => $this->getDate($date),
                    'title'         => (string)$item->title,
                    'description'   => (string)$item->description,
                    'url'           => (string)$item->link,
                );
            }
            if ($feedData) {
                Mage::getModel('adminnotification/inbox')->parse($feedData);
            }
        }
                
        return $this;
    }

    public function getFrequency()
    {
        return Mage::getStoreConfig(self::XML_FREQUENCY_PATH);
    }

    /* public function getLastUpdate()
    {
    	return Mage::app()->loadCache('megaventory_notifications_lastcheck');
    }
 
    public function setLastUpdate()
    {
        Mage::app()->saveCache(time(), 'megaventory_notifications_lastcheck');
        return $this;
    } */
    
    public function getLastUpdate() {
    	return Mage::getStoreConfig(self::XML_LAST_UPDATE_PATH);
    }
    
    public function setLastUpdate() {
    	Mage::getConfig()->saveConfig(self::XML_LAST_UPDATE_PATH, time(), "default", "0");
    	Mage::getConfig()->cleanCache();
    	return $this;
    }
    
    public function getFeedUrl()
    {
        if (is_null($this->_feedUrl)) {
            $this->_feedUrl = self::URL_NEWS;
        }
        return  $this->_feedUrl;
        //$query = '?s=' . urlencode(Mage::getStoreConfig('web/unsecure/base_url')); 
        //return $this->_feedUrl  . $query;
    }
    
	protected function isInteresting($item)
	{
		return true;
	}
    
}