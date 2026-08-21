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
import { IFinishedGoodsReceipt } from "../types";
import { formatDateDDMMYYYY } from "@/lib/utils";
import { EditOutlined, DeleteOutlined, EyeOutlined } from "@ant-design/icons";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { TooltipButton } from "@/components/tooltip-button";
import { useTranslations } from "next-intl";

const { Text } = Typography;

interface IFinishedGoodsReceiptCardProps {
  record: IFinishedGoodsReceipt;
  onEdit: (record: IFinishedGoodsReceipt) => void;
  onDelete: (record: IFinishedGoodsReceipt) => void;
  onView: (record: IFinishedGoodsReceipt) => void;
  deleting?: boolean;
}

const FinishedGoodsReceiptCard = ({
  record,
  onEdit,
  onDelete,
  onView,
  deleting,
}: IFinishedGoodsReceiptCardProps) => {
  const t = useTranslations("Factory.fg_receipt");
  const tc = useTranslations("Common");
  const tr = useTranslations("Factory.metadata.raw_material_tank");
  const tp = useTranslations("Factory.product_order");

  const getStatusColor = (status: string) => {
    switch (status) {
      case "in_progress":
        return "blue";
      case "completed":
        return "green";
      case "cancelled":
        return "red";
      default:
        return "default";
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case "in_progress":
        return t("in_progress");
      case "completed":
        return t("completed");
      case "cancelled":
        return t("cancelled");
      default:
        return status?.toUpperCase();
    }
  };

  const categoryLabel =
    record.product_type_category === "concentrated_latex"
      ? tp("concentrated_latex_label")
      : tr("scrap_rubber");

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
        <Space orientation="vertical" size={0} style={{ flex: 1 }}>
          <Text strong style={{ fontSize: "16px" }}>
            {record.finished_goods_receipt_code?.toUpperCase()}
          </Text>
          <Text type="secondary" style={{ fontSize: "12px" }}>
            {record.finished_goods_receipt_name}
          </Text>
        </Space>
        <Tag color={getStatusColor(record.status)} style={{ margin: 0 }}>
          {getStatusLabel(record.status)}
        </Tag>
      </Flex>

      <div style={{ height: "1px", background: "rgba(0,0,0,0.06)" }} />

      <Row gutter={[8, 8]}>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {t("order_code")}
            </Text>
            <Text strong>{record.production_order_name || "N/A"}</Text>
          </div>
        </Col>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {t("tank")}
            </Text>
            <Text strong>{record.product_tank_name || "N/A"}</Text>
          </div>
        </Col>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {tp("product")}
            </Text>
            <Text strong>{record.product_type_name}</Text>
          </div>
        </Col>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {tp("product_category")}
            </Text>
            <Text strong>{categoryLabel}</Text>
          </div>
        </Col>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {t("actual_quantity")}
            </Text>
            <Text strong>{record.actual_quantity.toLocaleString()}</Text>
          </div>
        </Col>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {t("actual_weight")}
            </Text>
            <Text strong>{record.actual_weight.toLocaleString()} kg</Text>
          </div>
        </Col>
      </Row>

      <div style={{ flexGrow: 1 }} />

      <div style={{ marginTop: "auto" }}>
        <Flex justify="flex-end" gap="small">
          <TooltipButton
            tooltip={t("view_tooltip")}
            type="dashed"
            icon={<EyeOutlined />}
            onClick={() => onView(record)}
          />
          <TooltipButton
            tooltip={t("edit_tooltip")}
            type="primary"
            ghost
            icon={<EditOutlined />}
            onClick={() => onEdit(record)}
          />
          <ConfirmTooltipButton
            confirmTitle={t("confirm_delete_title")}
            confirmDescription={t("confirm_delete_desc")}
            onConfirm={() => onDelete(record)}
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
        {tc("created_at")}: {formatDateDDMMYYYY(record.created_at)}
      </Text>
    </Card>
  );
};

export default FinishedGoodsReceiptCard;
