"use client";

import { Typography, Row, Col, Table, Divider, QRCode, Card } from "antd";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import { useTranslations } from "next-intl";
import { getLandByTransactionTicket } from "@/features/manage-land/land/actions";
import { formatDateDDMMYYYY, formatVnCurrency } from "@/lib/utils";
import { ITransactionTicket } from "../types";
import { generateQr } from "@/lib/api";
import Image from "next/image";

const { Title, Text } = Typography;

interface TransactionTicketInvoiceProps {
  ticket: ITransactionTicket;
}

const TransactionTicketInvoice: React.FC<TransactionTicketInvoiceProps> = ({
  ticket,
}) => {
  const t = useTranslations("TransactionTicket");
  const tf = useTranslations("ManageLand.Land");
  const tc = useTranslations("Common");
  const transactionTicketCode = ticket.transaction_ticket_code;

  const { data: linkedLands, isLoading: isLandsLoading } = useQuery({
    queryKey: ["transaction-ticket-lands", transactionTicketCode],
    queryFn: () =>
      getLandByTransactionTicket({
        transaction_ticket_code: transactionTicketCode,
        limit: 100,
        page: 1,
      }),
    enabled: !!transactionTicketCode,
  });

  const { data: genQr } = useQuery({
    queryKey: ["transaction-ticket-qr", ticket?.contract_code],
    queryFn: () =>
      generateQr({
        code: ticket?.contract_code || "",
        type: "transaction_ticket",
      }),
    enabled: !!ticket?.contract_code,
  });

  const lands = linkedLands?.data?.records || [];

  const productData = React.useMemo(() => {
    if (!ticket) return [];
    const products = [];
    if (Number(ticket.latex_weight) > 0) {
      products.push({
        key: "latex",
        stt: 1,
        type: `${t("latex")} (TSC: ${ticket.latex_tsc_grade})`,
        weight: ticket.latex_weight,
        unit: `${formatVnCurrency(ticket.latex_price_per_tsc)}đ/kg`,
        amount: formatVnCurrency(ticket.latex_total_amount),
      });
    }
    if (Number(ticket.scrap_rubber_weight) > 0) {
      products.push({
        key: "scrap",
        stt: products.length + 1,
        type: `${t("scrap_rubber")} (DRC: ${ticket.scrap_rubber_drc_grade})`,
        weight: ticket.scrap_rubber_weight,
        unit: `${formatVnCurrency(ticket.scrap_rubber_price_per_drc)}đ/kg`,
        amount: formatVnCurrency(ticket.scrap_rubber_total_amount),
      });
    }
    return products;
  }, [ticket, t]);

  const totalAmount =
    (ticket?.latex_total_amount || 0) +
    (ticket?.scrap_rubber_total_amount || 0);

  const productColumns = [
    { title: tc("index"), dataIndex: "stt", key: "stt", width: 60 },
    { title: t("latex_type"), dataIndex: "type", key: "type" },
    {
      title: t("weight_kg"),
      dataIndex: "weight",
      key: "weight",
      align: "right" as const,
    },
    {
      title: t("unit"),
      dataIndex: "unit",
      key: "unit",
      align: "right" as const,
    },
    {
      title: t("amount_vnd"),
      dataIndex: "amount",
      key: "amount",
      align: "right" as const,
    },
  ];

  const landColumns = [
    {
      title: tc("index"),
      dataIndex: "stt",
      key: "stt",
      width: 60,
      render: (_: any, __: any, index: number) => index + 1,
    },
    {
      title: tf("plot_code"),
      dataIndex: "plot_code",
      key: "plot_code",
      render: (text: string) => text?.toUpperCase(),
    },
    { title: tf("plot_name"), dataIndex: "plot_name", key: "plot_name" },
    {
      title: tf("area_ha"),
      dataIndex: "land_area",
      key: "land_area",
      align: "right" as const,
    },
    { title: tc("address"), dataIndex: "address", key: "address" },
    { title: tf("crop_type"), dataIndex: "crop_type", key: "crop_type" },
    {
      title: tf("planting_year"),
      dataIndex: "year_of_planting",
      key: "year_of_planting",
    },
  ];

  return (
    <Card
      className="invoice-card"
      style={{
        border: "1px solid #e8e8e8",
        padding: "12px",
        background: "#fff",
        boxShadow: "none",
      }}
      styles={{ body: { padding: 0 } }}>
      {/* Header section */}
      <Row gutter={24} align="middle">
        <Col span={6}>
          {genQr && genQr?.qr_code && (
            <Image src={genQr?.qr_code} alt="QR" width={160} height={160} />
          )}
        </Col>
        <Col span={12} style={{ textAlign: "center" }}>
          <Title level={4} style={{ margin: 0, textTransform: "uppercase" }}>
            {t("invoice_title")}
          </Title>
          <Text type="secondary" style={{ fontSize: "12px" }}>
            {t("electronic_invoice_representation")}
          </Text>
          <br />
          <Text type="secondary" style={{ fontSize: "12px" }}>
            {t("contract_code")}: {ticket?.contract_code}
          </Text>
          <br />
          <Text italic style={{ fontSize: "12px" }}>
            {tc("created_at")}:{" "}
            {formatDateDDMMYYYY(ticket?.created_at || new Date().toISOString())}
          </Text>
        </Col>
        <Col span={6} style={{ textAlign: "right" }}>
          <Text strong>
            {t("ticket_code")}: {transactionTicketCode?.toUpperCase()}
          </Text>
          <br />
        </Col>
      </Row>

      <Divider style={{ margin: "16px 0" }} />

      {/* Parties Section */}
      <Row gutter={48}>
        <Col span={12}>
          <Text strong style={{ textTransform: "uppercase" }}>
            {t("buyer_creator")}
          </Text>
          <div style={{ marginTop: "8px" }}>
            <Text style={{ display: "block" }}>
              {tc("full_name")}: {ticket?.buyer_name}
            </Text>
            <Text style={{ display: "block" }}>
              {tc("company")}: {ticket?.buyer_company_short_name}
            </Text>
            <Text style={{ display: "block" }}>
              {tc("address")}: {ticket?.buyer_address}
            </Text>
            <Text style={{ display: "block" }}>
              {tc("phone_number")}: {ticket?.buyer_phone}
            </Text>
          </div>
        </Col>
        <Col span={12}>
          <Text strong style={{ textTransform: "uppercase" }}>
            {t("seller")}
          </Text>
          <div style={{ marginTop: "8px" }}>
            <Text style={{ display: "block" }}>
              {tc("full_name")}: {ticket?.seller_name}
            </Text>
            <Text style={{ display: "block" }}>
              {tc("company")}: {ticket?.seller_company_short_name}
            </Text>
            <Text style={{ display: "block" }}>
              {tc("address")}: {ticket?.seller_address}
            </Text>
            <Text style={{ display: "block" }}>
              {tc("phone_number")}: {ticket?.seller_phone}
            </Text>
          </div>
        </Col>
      </Row>

      <div style={{ marginTop: "24px" }}>
        <Text strong style={{ textTransform: "uppercase" }}>
          {t("product_detail")}
        </Text>
        <Table
          dataSource={productData}
          columns={productColumns}
          pagination={false}
          size="small"
          bordered
          style={{ marginTop: "8px" }}
          summary={() => (
            <Table.Summary.Row style={{ background: "#f6ffed" }}>
              <Table.Summary.Cell index={0} colSpan={4} align="right">
                <Text strong>{tc("total")}</Text>
              </Table.Summary.Cell>
              <Table.Summary.Cell index={1} align="right">
                <Text strong style={{ color: "var(--ant-color-success)" }}>
                  {formatVnCurrency(totalAmount)}
                </Text>
              </Table.Summary.Cell>
            </Table.Summary.Row>
          )}
        />
      </div>

      <div style={{ marginTop: "24px" }}>
        <Text strong style={{ textTransform: "uppercase" }}>
          {t("land_info")}
        </Text>
        <Table
          dataSource={lands}
          columns={landColumns}
          pagination={false}
          size="small"
          rowKey="plot_code"
          bordered
          style={{ marginTop: "8px" }}
        />
      </div>

      <Row gutter={24} style={{ marginTop: "32px", textAlign: "center" }}>
        <Col span={12}>
          <Text strong style={{ textTransform: "uppercase" }}>
            {t("buyer")}
          </Text>
          <br />
          <Text type="secondary" italic style={{ fontSize: "11px" }}>
            {t("signature_label")}
          </Text>
          <div style={{ marginTop: "40px" }}>
            <Text italic>{t("signed")}</Text>
            <br />
            <Text strong>{ticket?.buyer_name}</Text>
          </div>
        </Col>
        <Col span={12}>
          <Text strong style={{ textTransform: "uppercase" }}>
            {t("seller")}
          </Text>
          <br />
          <Text type="secondary" italic style={{ fontSize: "11px" }}>
            {t("signature_label")}
          </Text>
          <div style={{ marginTop: "40px" }}>
            <Text italic>{t("signed")}</Text>
            <br />
            <Text strong>{ticket?.seller_name}</Text>
          </div>
        </Col>
      </Row>

      <div style={{ marginTop: "24px" }}>
        <Text strong style={{ fontSize: "12px" }}>
          {tc("notes")}:
        </Text>
        <ul style={{ paddingLeft: "16px", margin: 0, fontSize: "12px" }}>
          <li>
            {ticket?.latex_notes || ticket?.scrap_rubber_notes
              ? `${ticket.latex_notes} ${ticket.scrap_rubber_notes}`
              : tc("no_notes")}
          </li>
        </ul>
      </div>
    </Card>
  );
};

export default TransactionTicketInvoice;
