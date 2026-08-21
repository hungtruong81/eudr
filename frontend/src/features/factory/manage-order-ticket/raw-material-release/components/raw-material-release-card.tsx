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
import { IRawMaterialRelease } from "../types";
import { formatDateDDMMYYYY } from "@/lib/utils";
import { EditOutlined, DeleteOutlined, EyeOutlined } from "@ant-design/icons";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { CRUD } from "@/types/permission-context";
import { TooltipButton } from "@/components/tooltip-button";
import { useTranslations } from "next-intl";

const { Text } = Typography;

interface IRawMaterialReleaseCardProps {
  record: IRawMaterialRelease;
  onEdit: (record: IRawMaterialRelease) => void;
  onDelete: (record: IRawMaterialRelease) => void;
  onView: (record: IRawMaterialRelease) => void;
  deleting?: boolean;
  permission: CRUD;
}

const RawMaterialReleaseCard = ({
  record,
  onEdit,
  onDelete,
  onView,
  deleting,
  permission,
}: IRawMaterialReleaseCardProps) => {
  const t = useTranslations("Factory.material_release");
  const tc = useTranslations("Common");

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
        return tc("completed");
      case "cancelled":
        return tc("cancelled");
      default:
        return status?.toUpperCase();
    }
  };

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
            {record.material_release_code?.toUpperCase()}
          </Text>
          <Text type="secondary" style={{ fontSize: "12px" }}>
            {record.material_release_name}
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
            <Text strong>{record.production_order_code || "N/A"}</Text>
          </div>
        </Col>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {tc("created_at")}
            </Text>
            <Text strong>{formatDateDDMMYYYY(record.created_at)}</Text>
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
            <Text type="secondary">{t("requested_weight_total")}</Text>
            <Text
              strong
              style={{ fontSize: "18px", color: "var(--ant-color-primary)" }}>
              {record.total_requested_weight.toLocaleString()} kg
            </Text>
          </Flex>
        </Col>
      </Row>

      <div style={{ flexGrow: 1 }} />

      <div style={{ marginTop: "auto" }}>
        <Flex justify="flex-end" gap="small">
          {(permission.full || permission.view) && (
            <TooltipButton
              tooltip={t("view_tooltip")}
              type="dashed"
              icon={<EyeOutlined />}
              onClick={() => onView(record)}
            />
          )}
          {(permission.update || permission.full) && (
            <TooltipButton
              tooltip={t("edit_tooltip")}
              type="primary"
              ghost
              icon={<EditOutlined />}
              onClick={() => onEdit(record)}
            />
          )}
          {(permission.delete || permission.full) && (
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
          )}
        </Flex>
      </div>
    </Card>
  );
};

export default RawMaterialReleaseCard;
