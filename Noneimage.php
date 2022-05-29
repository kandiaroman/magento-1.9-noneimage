<?php

/**
 * Noneimage
 */
class Vendor_Module_Model_Product_Noneimage {

  // But exists on disk
  protected $none_exists_in_db = array();

  // But exists in db
  protected $none_exists_on_disk = array();

  protected $db_images = array();

  protected $disk_images = array();

  public function clean() {
    if (empty($this->none_exists_in_db) && empty($this->none_exists_on_disk)) {
      throw new Exception('There is nothing to clean!.');
    }

    $result = array();

    if (!empty($this->none_exists_on_disk)) {
      foreach ($this->none_exists_on_disk as $item) {
        $clean_item = sprintf(
          "DELETE FROM catalog_product_entity_media_gallery WHERE value = '%s'",
          str_replace($this->getMediaPath(), "", $item)
        );

        try {
          Mage::getSingleton('core/resource')
            ->getConnection('core_write')
            ->query($clean_item);

          $result['success']['cleaned_items_in_db'][] = $item;
        } catch (Exception $e) {
          $result['errors']['uncleaned_items_in_db'][] = $item;
        }
      }
    }

    if (!empty($this->none_exists_in_db)) {
      foreach ($this->none_exists_in_db as $item) {
        $clean_item = unlink($item);
        if (!$clean_item) {
          $result['errors']['uncleaned_items_on_disk'][] = $item;
          continue;
        }

        $result['success']['cleaned_items_on_disk'] = $item;
      }
    }

    return $result;
  }

  public function onDisk() {
    if (empty($this->none_exists_on_disk)) {
      $db_images = $this->getDbImages();
      $disk_images = $this->getDiskImages();
      foreach ($db_images as $image => $null) {
        if (isset($disk_images[$image])) {
          continue;
        } else {
          $this->none_exists_on_disk[] = $image;
        }
      }
    }

    return $this;
  }

  public function inDb() {
    if (empty($this->none_exists_in_db)) {
      $db_images = $this->getDbImages();
      $disk_images = $this->getDiskImages();
      foreach ($disk_images as $image => $null) {
        if (isset($db_images[$image])) {
          continue;
        } else {
          $this->none_exists_in_db[] = $image;
        }
      }
    }

    return $this;
  }

  protected function getDbImages() {
    if (empty($this->db_images)) {
      $db_images_query = sprintf(
        "SELECT CONCAT('%s', '', value), REPLACE(attribute_id, attribute_id, '') FROM catalog_product_entity_media_gallery",
        $this->getMediaPath()
      );

      $this->db_images = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchPairs($db_images_query);
    }

    return $this->db_images;
  }

  protected function getMediaPath() {
    return Mage::getBaseDir('media') . '/catalog/product';
  }

  protected function getDirs($path) {
    $dirs = array();
    foreach (glob("{$path}/*", GLOB_ONLYDIR) as $dir) {
      if (strpos($dir, 'cache') !== false) {
        continue;
      }

      $dirs[] = $dir;

      $dirs = array_merge($dirs, $this->getDirs($dir));
    }

    return $dirs;
  }

  protected function getDiskImages() {
    if (empty($this->disk_images)) {
      $dirs = $this->getDirs($this->getMediaPath());
      foreach ($dirs as $dir) {
        foreach (glob("{$dir}/*.*") as $image) {
          $this->disk_images[$image] = '';
        }
      }
    }

    return $this->disk_images;
  }
}
