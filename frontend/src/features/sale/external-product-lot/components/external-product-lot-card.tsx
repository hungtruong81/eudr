"use client";

import React from "react";
import { Card, Typography, Space, Tag, Flex, Row, Col } from "antd";
import {
  EditOutlined,
  EyeOutlined,
  CheckOutlined,
  StopOutlined,
  DeleteOutlined,
} from "@ant-design/icons";
import { formatDateDDMMYYYY } from "@/lib/utils";
import { TooltipButton } from "@/components/tooltip-button";
import { IProductLotExternal } from "../types";
import { Popconfirm } from "antd";
import { useTranslations } from "next-intl";

const { Text } = Typography;

interface Props {
  record: IProductLotExternal;
  onEdit: (record: IProductLotExternal) => void;
  onDelete: (record: IProductLotExternal) => void;
  onView: (record: IProductLotExternal) => void;
  onConfirm: (record: IProductLotExternal) => void;
  onCancel: (record: IProductLotExternal) => void;
  deleting?: boolean;
  confirming?: boolean;
  cancelling?: boolean;
}

export const getStatusColor = (status: string) => {
  switch (status) {
    case "draft":
      return "default";
    case "confirmed":
      return "green";
    case "shipped":
      return "blue";
    case "cancelled":
      return "red";
    default:
      return "default";
  }
};

export const getStatusLabel = (status: string, t: any) => {
  if (t && status) {
    try {
      return t(status);
    } catch (e) {
      // Fallback if translation fails
    }
  }
  switch (status) {
    case "draft":
      return "Lưu nháp";
    case "confirmed":
      return "Đã xác nhận";
    case "shipped":
      return "Đã giao hàng";
    case "cancelled":
      return "Đã hủy";
    default:
      return status?.toUpperCase() || "—";
  }
};
const ExternalProductLotCard = ({
  record,
  onEdit,
  onDelete,
  onView,
  onConfirm,
  onCancel,
  deleting,
  confirming,
  cancelling,
}: Props) => {
  const t = useTranslations("Sales.external_product_lot");
  const tCommon = useTranslations("Common");
  const tStatus = useTranslations("Status");

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
          <Space>
            <Text strong style={{ fontSize: "16px" }}>
              {record.product_lot_code?.toUpperCase()}
            </Text>
          </Space>
          <Text type="secondary" style={{ fontSize: "12px" }}>
            {record.supplier_company_name}
          </Text>
        </Space>
        <Tag color={getStatusColor(record.status)} style={{ margin: 0 }}>
          {getStatusLabel(record.status, tStatus)}
        </Tag>
      </Flex>

      <div style={{ height: "1px", background: "rgba(0,0,0,0.06)" }} />

      <Row gutter={[8, 8]}>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {tCommon("grade")}
            </Text>
            <Text strong>{record.grade || "N/A"}</Text>
          </div>
        </Col>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {t("total_blocks")}
            </Text>
            <Text strong>{record.total_blocks?.toLocaleString()}</Text>
          </div>
        </Col>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {tCommon("weight")}
            </Text>
            <Text strong>
              {Number(record.total_weight)?.toLocaleString()} kg
            </Text>
          </div>
        </Col>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {tCommon("from_date")}
            </Text>
            <Text strong>
              {formatDateDDMMYYYY(record.production_date_from)}
            </Text>
          </div>
        </Col>
      </Row>

      <div style={{ flexGrow: 1 }} />

      <div style={{ marginTop: "auto" }}>
        <Flex justify="flex-end" gap="small">
          {record.status === "draft" && (
            <Popconfirm
              title={t("confirm_lot_title")}
              onConfirm={() => onConfirm(record)}
              okText={tCommon("confirm")}
              cancelText={tCommon("cancel")}>
              <TooltipButton
                tooltip={tCommon("confirm")}
                type="primary"
                ghost
                icon={<CheckOutlined />}
                loading={confirming}
              />
            </Popconfirm>
          )}

          {record.status === "confirmed" && (
            <Popconfirm
              title={t("cancel_lot_title")}
              onConfirm={() => onCancel(record)}
              okText={tCommon("cancel")}
              cancelText={tCommon("back")}
              okButtonProps={{ danger: true }}>
              <TooltipButton
                tooltip={tCommon("cancel")}
                danger
                icon={<StopOutlined />}
                loading={cancelling}
              />
            </Popconfirm>
          )}

          <TooltipButton
            tooltip={tCommon("view")}
            type="dashed"
            icon={<EyeOutlined />}
            onClick={() => onView(record)}
          />

          {/* {record.status === "draft"   && (
            <TooltipButton
              tooltip={tCommon("edit")}
              type="primary"
              ghost
              icon={<EditOutlined />}
              onClick={() => onEdit(record)}
            />
          )} */}

          {record.status === "draft" && (
            <Popconfirm
              title={t("delete_lot_title")}
              description={tCommon("irreversible_action")}
              onConfirm={() => onDelete(record)}
              okText={tCommon("delete")}
              cancelText={tCommon("cancel")}
              okButtonProps={{ danger: true, loading: deleting }}>
              <TooltipButton
                tooltip={tCommon("delete")}
                danger
                icon={<DeleteOutlined />}
              />
            </Popconfirm>
          )}
        </Flex>
      </div>
    </Card>
  );
};

export default ExternalProductLotCard;
