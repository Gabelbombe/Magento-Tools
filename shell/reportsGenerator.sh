#!/bin/bash
# CPR : Jd Daniel :: Ehime-ken
# MOD : 2014-10-04 @ 12:51:52
# VER : 1.0
#
# Magento report module skeleton
#

echo -e "Generic module creator\n"
read -p "Company name: " company
read -p "Modules name: " module
read -p "Install dir:  " directory

[ -d ${directory} ] && {
    cd ${directory} 
} || {
    echo -e "Directory '${directory}' not valid, quiting"
    exit 1
}

    modlower="$(echo ${module} |awk '{print tolower($0)}')"

## declare an array
declare -a paths=(
    "app/etc/modules/${company}_${module}.xml"
    "app/design/adminhtml/default/default/layout/${modlower}.xml"
    "app/code/local/${company}/${module}/Block/adminhtml/${module}/Grid.php"
    "app/code/local/${company}/${module}/Block/adminhtml/${module}.php"
    "app/code/local/${company}/${module}/Block/${module}.php"
    "app/code/local/${company}/${module}/controllers/Adminhtml/${module}Controller.php"
    "app/code/local/${company}/${module}/etc/config.xml"
    "app/code/local/${company}/${module}/Helper/Data.php"
    "app/code/local/${company}/${module}/Model/${module}.php"
)

for file in "${paths[@]}"; do

    ## create if not exist
    [ -d "$(dirname ${file})" ] || {
        mkdir -p "$(dirname ${file})"
    }

    touch "${file}"
done

## Define module
echo "<?xml version='1.0'?>
<config>
    <modules>
        <${company}_${module}>
            <active>true</active>
            <codePool>local</codePool>
        </${company}_${module}>
    </modules>
</config>" > "app/etc/modules/${company}_${module}.xml"


## Create layout file for admin view
echo "<?xml version='1.0'?>
<layout version='0.1.0'>
    <${modlower}_adminhtml_${modlower}_index>
        <reference name='content'>
            <block type='${modlower}/adminhtml_${modlower}' name='${modlower}' />
        </reference>
    </${modlower}_adminhtml_${modlower}_index>
</layout>" > "app/design/adminhtml/default/default/layout/${modlower}.xml"


## Create the config file
echo "<?xml version='1.0'?>
<!--
/**
 * @category   ${company}
 * @package    ${company}_${module}
 * @author     Jd Daniel
 */
 -->
<config>
    <modules>
        <${company}_${module}>
            <version>0.1.0</version>
        </${company}_${module}>
    </modules>
    <admin>
        <routers>
            <${modlower}>
                <use>admin</use>
                <args>
                    <module>${company}_${module}</module>
                    <frontName>${modlower}</frontName>
                </args>
            </${modlower}>
        </routers>
    </admin>
    <adminhtml>
        <menu>
            <report>
                <children>
                    <${modlower} translate='title' module='${modlower}'>
                        <title>${module} Report</title>
                        <action>${modlower}/adminhtml_${modlower}</action>
                    </${modlower}>
                </children>
            </report>
        </menu>
        <acl>
            <resources>
                <all>
                    <title>Allow Everything</title>
                </all>
                <admin>
                    <children>
                        <report>
                            <children>
                                <${modlower} translate='title' module='${modlower}'>
                                    <title>${module} Report</title>
                                    <action>${modlower}/adminhtml_${modlower}</action>
                                </${modlower}>
                            </children>
                        </report>
                    </children>
                </admin>
            </resources>
        </acl>
        <layout>
            <updates>
                <${modlower}>
                    <file>${modlower}.xml</file>
                </${modlower}>
            </updates>
        </layout>
    </adminhtml>
    <global>
        <models>
            <${modlower}>
                <class>${company}_${module}_Model</class>
                <resourceModel>${modlower}</resourceModel>
            </${modlower}>
        </models>
        <resources>
            <${modlower}_setup>
                <setup>
                    <module>${company}_${module}</module>
                </setup>
                <connection>
                    <use>core_setup</use>
                </connection>
            </${modlower}_setup>
            <${modlower}_write>
                <connection>
                    <use>core_write</use>
                </connection>
            </${modlower}_write>
            <${modlower}_read>
                <connection>
                    <use>core_read</use>
                </connection>
            </${modlower}_read>
        </resources>
        <blocks>
            <${modlower}>
                <class>${company}_${module}_Block</class>
            </${modlower}>
        </blocks>
        <helpers>
            <${modlower}>
                <class>${company}_${module}_Helper</class>
            </${modlower}>
        </helpers>
    </global>
</config>" > "app/code/local/${company}/${module}/etc/config.xml"


## Create the grid and specify generic columns
echo "<?php
Class ${company}_${module}_Block_Adminhtml_${module}_Grid Extends Mage_Adminhtml_Block_Report_Grid
{
    public function __construct()
    {
        parent::__construct();

        \$this->setId('${modlower}Grid');
        \$this->setDefaultSort('created_at');
        \$this->setDefaultDir('ASC');
        \$this->setSaveParametersInSession(true);
        \$this->setSubReportSize(false);
    }

    protected function _prepareCollection()
    {
        parent::_prepareCollection();

        \$this->getCollection()->initReport('${modlower}/${modlower}'); //indicator for model used to get data.

            return \$this;
    }

    protected function _prepareColumns()
    {
        \$this->addColumn('ordered_qty', array(
            'header'    =>Mage::helper('reports')->__('Quantity Ordered'),
            'align'     =>'right',
            'index'     =>'ordered_qty',
            'type'      =>'number'
            'total'     =>'sum',    //indicator that this field must be totalized at the end.
        ));
        \$this->addColumn('item_id', array(
            'header' => Mage::helper('${modlower}')->__('Item ID'),
            'align'  => 'right',
            'index'  => 'item_id',
            'type'   => 'number',
            'total'  => 'sum',
        ));
        \$this->addExportType('*/*/exportCsv',  Mage::helper('${modlower}')->__('CSV'));
        \$this->addExportType('*/*/exportXml',  Mage::helper('${modlower}')->__('XML'));
        \$this->addExportType('*/*/exportJson', Mage::helper('${modlower}')->__('JSON'));

            return parent::_prepareColumns();
    }

    public function getRowUrl(\$row)
    {
        return false;
    }

    public function getReport(\$from, \$to)
    {
        if (empty(\$from)) \$from = \$this->getFilter('report_from');
        if (empty(\$to))   \$to   = \$this->getFilter('report_to');

        \$totalObj = Mage::getModel('reports/totals');
        \$totals = \$totalObj->countTotals(\$this, \$from, \$to);

        \$this->setTotals(\$totals);
        \$this->addGrandTotals(\$totals);

            return \$this->getCollection()->getReport(\$from, \$to);
    }
}" > "app/code/local/${company}/${module}/Block/adminhtml/${module}/Grid.php"


## Create grid container block
echo "<?php
Class ${company}_${module}_Block_Adminhtml_${module} Extends Mage_Adminhtml_Block_Widget_Grid_Container 
{
    public function __construct() 
    {
        \$this->_controller = 'adminhtml_${modlower}';
        \$this->_blockGroup = '${modlower}';
        \$this->_headerText = Mage::helper('${modlower}')->__('${module} Report');

            parent::__construct();

        \$this->_removeButton('add');
    }
}" > "app/code/local/${company}/${module}/Block/adminhtml/${module}.php"


## Create the block container
echo "<?php
Class ${company}_${module}_Block_${module} Extends Mage_Core_Block_Template
{
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function get${module}()
    {
        if (!  \$this->hasData('${modlower}')) \$this->setData('${modlower}', Mage::registry('${modlower}'));

            return \$this->getData('${modlower}');
    }
}" > "app/code/local/${company}/${module}/Block/${module}.php"


## Create controller
echo "<?php
Class ${company}_${module}_Adminhtml_${module}Controller Extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        \$this->loadLayout();

            return \$this;
    }

    public function indexAction()
    {
        \$this->_initAction()->renderLayout();
    }

    public function exportCsvAction()
    {
        \$this->_sendUploadResponse('${modlower}.csv', \$this->getLayout()->createBlock('${modlower}/adminhtml_${modlower}_grid')->getCsv());

            return \$this;
    }

    public function exportXmlAction()
    {
        \$this->_sendUploadResponse('${modlower}.xml', \$this->getLayout()->createBlock('${modlower}/adminhtml_${modlower}_grid')->getXml());

            return \$this;
    }

    public function exportJsonAction()
    {
        \$this->_sendUploadResponse('${modlower}.json', \$this->getLayout()->createBlock('${modlower}/adminhtml_${modlower}_grid')->getJson());

            return \$this;
    }

    protected function _sendUploadResponse(\$fileName, \$content, \$contentType='application/octet-stream') {
        \$response = \$this->getResponse();

        \$response->setHeader('HTTP/1.1 200 OK', '');
        \$response->setHeader('Pragma', 'public', true);
        \$response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        \$response->setHeader('Content-Disposition', 'attachment; filename=' . \$fileName);
        \$response->setHeader('Last-Modified', date('r'));
        \$response->setHeader('Accept-Ranges', 'bytes');
        \$response->setHeader('Content-Length', strlen(\$content));
        \$response->setHeader('Content-type', \$contentType);
        \$response->setBody(\$content);
        \$response->sendResponse();

            exit;
    }
}" > "app/code/local/${company}/${module}/controllers/Adminhtml/${module}Controller.php"


## Empty helper
echo "<?php
Class ${company}_${module}_Helper_Data Extends Mage_Core_Helper_Abstract
{
    // ....
}" > "app/code/local/${company}/${module}/Helper/Data.php"


## Create model
echo "<?php
Class ${company}_${module}_Model_${module} Extends Mage_Reports_Model_Mysql4_Order_Collection
{
    public function __construct() 
    {
        parent::__construct();

        \$this->setResourceModel('sales/order_item');
        \$this->_init('sales/order_item','item_id');
   }
 
    public function setDateRange(\$from, \$to) 
    {
        \$this->_reset();
        \$this->getSelect()
             ->joinInner(array(
                 'i' => \$this->getTable('sales/order_item')),
                 'i.order_id = main_table.entity_id'
                 )
             ->where('i.parent_item_id is null')
             ->where(\"i.created_at BETWEEN {\$from} AND {\$to}\")
             ->where('main_table.state = \'complete\'')
             ->columns(array('ordered_qty' => 'count(distinct main_table.entity_id)'));

        // uncomment next line to get the query log:
        // Mage::log('SQL: '.\$this->getSelect()->__toString());

            return \$this;
    }
 
    public function setStoreIds(\$storeIds)
    {
        return \$this;
    }
}" > "app/code/local/${company}/${module}/Model/${module}.php"


echo -e "Done!\n\nCreated: $(tree app/)"