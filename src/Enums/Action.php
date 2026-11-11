<?php

declare(strict_types=1);

namespace Szabrob\LaravelSzamlazz\Enums;

/**
 * @internal Számla Agent actions: multipart field name, XML root element and namespace.
 */
enum Action: string
{
    case CreateInvoice = 'xmlszamla';
    case ReverseInvoice = 'xmlszamlast';
    case RegisterPayment = 'xmlszamlakifiz';
    case InvoicePdf = 'xmlszamlapdf';
    case InvoiceData = 'xmlszamlaxml';
    case DeleteProforma = 'xmlszamladbkdel';
    case CreateReceipt = 'xmlnyugtacreate';
    case ReverseReceipt = 'xmlnyugtast';
    case GetReceipt = 'xmlnyugtaget';
    case SendReceipt = 'xmlnyugtasend';

    public function field(): string
    {
        return match ($this) {
            self::CreateInvoice => 'action-xmlagentxmlfile',
            self::ReverseInvoice => 'action-szamla_agent_st',
            self::RegisterPayment => 'action-szamla_agent_kifiz',
            self::InvoicePdf => 'action-szamla_agent_pdf',
            self::InvoiceData => 'action-szamla_agent_xml',
            self::DeleteProforma => 'action-szamla_agent_dijbekero_torlese',
            self::CreateReceipt => 'action-szamla_agent_nyugta_create',
            self::ReverseReceipt => 'action-szamla_agent_nyugta_storno',
            self::GetReceipt => 'action-szamla_agent_nyugta_get',
            self::SendReceipt => 'action-szamla_agent_nyugta_send',
        };
    }

    public function rootElement(): string
    {
        return $this->value;
    }

    public function namespace(): string
    {
        return 'http://www.szamlazz.hu/'.$this->value;
    }
}
