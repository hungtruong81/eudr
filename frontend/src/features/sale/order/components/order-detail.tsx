import BaseSheet from "@/components/shared/base-sheet";
import { Table, Tag, Typography, Divider, Card } from "antd";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import { getOrderByCode } from "../actions";
import { IOrder, IOrderItem } from "../types";
import { formatVnCurrency } from "@/lib/utils";
import { useTranslations, useLocale } from "next-intl";

const { Text } = Typography;

interface OrderDetailProps {
  open: boolean;
  onClose: () => void;
  record: IOrder | null;
}

const OrderDetail = ({ open, onClose, record }: OrderDetailProps) => {
  const ts = useTranslations("Sales");
  const tst = useTranslations("Status");
  const tc = useTranslations("Common");
  const ta = useTranslations("Account");
  const locale = useLocale();

  const { data: orderData, isLoading } = useQuery({
    queryKey: ["order-detail", record?.sale_order_code],
    queryFn: () => getOrderByCode(record?.sale_order_code || ""),
    enabled: !!record?.sale_order_code && open,
  });

  const detailColumns = [
    {
      title: ta("info"),
      dataIndex: "label",
      key: "label",
      width: "40%",
      render: (text: string) => <Text type="secondary">{text}</Text>,
    },
    {
      title: tc("detail"),
      dataIndex: "value",
      key: "value",
      render: (text: any) => <Text strong>{text ?? "—"}</Text>,
    },
  ];

  const order = orderData?.order || record;

  const getStatusTag = (status: string) => {
    let color = "blue";
    let text = status;
    switch (status) {
      case "draft":
        color = "blue";
        text = tst("draft");
        break;
      case "approved":
        color = "green";
        text = tst("approved");
        break;
      case "allocated":
        color = "cyan";
        text = tst("allocated");
        break;
      case "shipping":
        color = "orange";
        text = tst("delivering");
        break;
      case "closed":
        color = "gray";
        text = tst("closed");
        break;
      case "cancelled":
        color = "red";
        text = tst("cancelled");
        break;
      case "pending":
        color = "orange";
        text = tst("pending");
        break;
    }
    return <Tag color={color}>{text}</Tag>;
  };

  const itemColumns = [
    {
      title: ts("product"),
      dataIndex: "product_type_name",
      key: "product_type_name",
      render: (text: string, record: IOrderItem) => (
        <div>
          <Text strong>{text || record.product_type_code}</Text>
          <div className="text-xs text-gray-400">
            {record.product_type_category}
          </div>
        </div>
      ),
    },
    {
      title: ts("tank"),
      dataIndex: "product_tank_name",
      key: "product_tank_name",
      render: (text: string, record: IOrderItem) =>
        text ||
        record.raw_material_tank_name ||
        record.transaction_ticket_code ||
        "-",
    },
    {
      title: ts("quantity"),
      dataIndex: "qty_ordered",
      key: "qty_ordered",
      align: "right" as const,
      render: (val: number, record: IOrderItem) =>
        `${val} ${record.uom || "kg"}`,
    },
    {
      title: ts("price"),
      dataIndex: "price",
      key: "price",
      align: "right" as const,
      render: (val: number) => formatVnCurrency(val),
    },
    {
      title: ts("subtotal"),
      key: "total",
      align: "right" as const,
      render: (_: any, record: IOrderItem) =>
        formatVnCurrency(record.qty_ordered * record.price),
    },
  ];

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      title={`${ts("order_detail")}: ${record?.sale_order_code?.toUpperCase() || ""}`}
      loading={isLoading}
      width={1000}>
      {order && (
        <div style={{ padding: "0 12px" }}>
          <Table
            title={() => <Text strong>{ts("general_info")}</Text>}
            dataSource={[
              {
                label: ts("order_code"),
                value: order.sale_order_code,
              },
              {
                label: ts("status"),
                value: getStatusTag(order.status),
              },
              {
                label: ts("order_date"),
                value: order.order_date
                  ? new Date(order.order_date).toLocaleDateString(
                      locale === "vi" ? "vi-VN" : "en-US",
                    )
                  : "-",
              },
              {
                label: ts("delivery_date"),
                value: order.delivery_date
                  ? new Date(order.delivery_date).toLocaleDateString(
                      locale === "vi" ? "vi-VN" : "en-US",
                    )
                  : "-",
              },
              {
                label: ts("source"),
                value:
                  order.order_source_type === "warehouse"
                    ? ts("warehouse")
                    : ts("purchase_order.transaction_ticket"),
              },
              {
                label: ts("total_amount"),
                value: (
                  <Text type="danger" strong>
                    {formatVnCurrency(order.total_amount)}
                  </Text>
                ),
              },
            ]}
            columns={detailColumns}
            pagination={false}
            size="small"
            bordered
            rowKey="label"
            showHeader={false}
          />

          <Divider />

          <Table
            title={() => <Text strong>{ts("customer_info")}</Text>}
            dataSource={[
              {
                label: ta("full_name"),
                value: order.customer_name || order.buyer_company_name,
              },
              { label: ta("phone"), value: order.customer_phone },
              { label: ta("email"), value: order.customer_email },
              { label: ta("tax_code"), value: order.tax_code },
              { label: ts("delivery_address"), value: order.delivery_address },
            ]}
            columns={detailColumns}
            pagination={false}
            size="small"
            bordered
            rowKey="label"
            showHeader={false}
          />

          <Divider />

          <Typography.Title level={5}>{ts("product_list")}</Typography.Title>
          <Table
            dataSource={order.items || []}
            columns={itemColumns}
            pagination={false}
            rowKey="sale_order_item_id"
            bordered
            summary={(pageData) => {
              let totalQty = 0;
              pageData.forEach(({ qty_ordered }) => {
                totalQty += qty_ordered;
              });

              return (
                <Table.Summary.Row>
                  <Table.Summary.Cell index={0} colSpan={2}>
                    <Text strong>{tc("total")}</Text>
                  </Table.Summary.Cell>
                  <Table.Summary.Cell index={1} align="right">
                    <Text strong>{totalQty} kg</Text>
                  </Table.Summary.Cell>
                  <Table.Summary.Cell index={2} />
                  <Table.Summary.Cell index={3} align="right">
                    <Text type="danger" strong>
                      {formatVnCurrency(order.total_amount)}
                    </Text>
                  </Table.Summary.Cell>
                </Table.Summary.Row>
              );
            }}
          />

          {order.notes && (
            <>
              <Divider />
              <Card size="small" title={ts("notes")}>
                <Text italic>{order.notes}</Text>
              </Card>
            </>
          )}
        </div>
      )}
    </BaseSheet>
  );
};

export default OrderDetail;
