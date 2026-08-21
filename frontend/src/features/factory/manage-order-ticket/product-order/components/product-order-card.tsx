import React from "react";
import {
  Card,
  Flex,
  Typography,
  Tag,
  Space,
  Row,
  Col,
} from "antd";
import { IProductionOrder } from "../types";
import { formatDateDDMMYYYY } from "@/lib/utils";
import { EditOutlined, DeleteOutlined } from "@ant-design/icons";
import { TooltipButton } from "@/components/tooltip-button";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { useTranslations } from "next-intl";

const { Text } = Typography;

interface IProductOrderCardProps {
  order: IProductionOrder;
  onEdit: (order: IProductionOrder) => void;
  onDelete: (code: string) => void;
  deleting?: boolean;
}

const ProductOrderCard = ({
  order,
  onEdit,
  onDelete,
  deleting,
}: IProductOrderCardProps) => {
  const t = useTranslations("Factory.product_order");
  const tc = useTranslations("Common");
  const tr = useTranslations("Factory.metadata.raw_material_tank");

  const getStatusColor = (status: string) => {
    switch (status) {
      case "approved":
        return "blue";
      case "in_production":
        return "orange";
      case "completed":
        return "green";
      default:
        return "default";
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case "approved":
        return t("approved");
      case "in_production":
        return t("in_production");
      case "completed":
        return t("completed");
      default:
        return status?.toUpperCase();
    }
  };

  const productTypeCategoryLabel =
    order.product_type_category === "latex" ? tr("latex") : tr("scrap_rubber");

  const groupCellStyle: React.CSSProperties = {
    border: "1px solid var(--ant-color-border)",
    borderRadius: "8px",
    padding: "12px",
    height: "100%",
    display: "flex",
    flexDirection: "column",
    gap: "4px",
  };

  return (
    <Card
      style={{ width: "100%", height: "100%" }}
      styles={{
        body: {
          padding: "16px",
          display: "flex",
          flexDirection: "column",
          gap: "12px",
          height: "100%",
        },
      }}>
      <Flex justify="space-between" align="flex-start">
        <Space orientation="vertical" size={0}>
          <Text strong style={{ fontSize: "16px" }}>
            {order.production_order_code?.toUpperCase()}
          </Text>
          <Text type="secondary" style={{ fontSize: "12px" }}>
            {order.production_order_name}
          </Text>
        </Space>
        <Tag color={getStatusColor(order.status)} style={{ margin: 0 }}>
          {getStatusLabel(order.status)}
        </Tag>
      </Flex>

      <div style={{ height: "1px", background: "rgba(0,0,0,0.06)" }} />

      <Row gutter={[8, 8]}>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {t("contract")}
            </Text>
            <Text strong>{order.contract_code || "N/A"}</Text>
          </div>
        </Col>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {t("production_date")}
            </Text>
            <Text strong>{formatDateDDMMYYYY(order.production_date)}</Text>
          </div>
        </Col>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {t("rubber_type")}
            </Text>
            <Text strong>{productTypeCategoryLabel}</Text>
          </div>
        </Col>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {t("product")}
            </Text>
            <Text strong>{order.product_type_name}</Text>
          </div>
        </Col>
        <Col span={24}>
          <Flex
            justify="space-between"
            align="center"
            style={{
              background: "#f5f5f5",
              padding: "8px 12px",
              borderRadius: "8px",
            }}>
            <Text type="secondary">{t("required_quantity")}</Text>
            <Text
              strong
              style={{ fontSize: "18px", color: "var(--ant-color-primary)" }}>
              {order.required_quantity.toLocaleString()} kg
            </Text>
          </Flex>
        </Col>
      </Row>

      <div style={{ flexGrow: 1 }} />

      <div>
        <Flex justify="flex-end" gap="small">
          <TooltipButton
            tooltip={t("edit_tooltip")}
            type="primary"
            ghost
            icon={<EditOutlined />}
            onClick={() => onEdit(order)}
          />
          <ConfirmTooltipButton
            confirmTitle={t("confirm_delete_title")}
            confirmDescription={t("confirm_delete_desc")}
            onConfirm={() => onDelete(order.production_order_code)}
            loading={deleting}
            tooltip={t("delete_tooltip")}
            danger
            ghost
            icon={<DeleteOutlined />}
          />
        </Flex>
      </div>

      <Text
        type="secondary"
        style={{ fontSize: "11px", textAlign: "right", marginTop: "4px" }}>
        {tc("created_at")}: {formatDateDDMMYYYY(order.created_at)}
      </Text>
    </Card>
  );
};

export default ProductOrderCard;
