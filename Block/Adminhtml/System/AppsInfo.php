<?php

declare(strict_types=1);

namespace Solutioo\Base\Block\Adminhtml\System;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Renders Solutioo apps overview inside Stores → Configuration → Solutioo → Base.
 */
class AppsInfo extends Field
{
    protected $_template = 'Solutioo_Base::system/apps-info.phtml';

    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return $this->_decorateRowHtml($element, $this->_toHtml());
    }

    protected function _decorateRowHtml(AbstractElement $element, $html): string
    {
        return sprintf(
            '<tr id="row_%s"><td colspan="5" class="solutioo-apps-info-cell">%s</td></tr>',
            $element->getHtmlId(),
            $html
        );
    }
}
