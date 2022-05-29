Usage: Replace 'Vendor' and 'Module' with your vendor and module name and place in the appropriate environment.

Example of cleaning:

$noneimages = Mage::getModel('vendor_module/product_noneimage')
  ->inDb()
  ->onDisk();

$result = $noneimages->clean();
