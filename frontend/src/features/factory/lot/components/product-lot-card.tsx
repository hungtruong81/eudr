import React from "react";
import {
  Card,
  Flex,
  Typography,
  Tag,
  Space,
  Button,
  Popconfirm,
  Row,
  Col,
} from "antd";
import { IProductLot } from "../types";
import { formatDateDDMMYYYY } from "@/lib/utils";
import { EditOutlined, DeleteOutlined, EyeOutlined } from "@ant-design/icons";
import { TooltipButton } from "@/components/tooltip-button";
import { useTranslations } from "next-intl";

const { Text } = Typography;

interface IProductLotCardProps {
  record: IProductLot;
  onEdit: (record: IProductLot) => void;
  onDelete: (record: IProductLot) => void;
  onView: (record: IProductLot) => void;
  deleting?: boolean;
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

export const getStatusLabel = (status: string, ts: any) => {
  switch (status) {
    case "draft":
      return ts("draft");
    case "confirmed":
      return ts("confirmed");
    case "shipped":
      return ts("shipped");
    case "cancelled":
      return ts("cancelled");
    default:
      return status?.toUpperCase();
  }
};

const ProductLotCard = ({
  record,
  onEdit,
  onDelete,
  onView,
  deleting,
}: IProductLotCardProps) => {
  const t = useTranslations("Factory");
  const ts = useTranslations("Status");
  const tc = useTranslations("Common");
  const tsa = useTranslations("Sales");
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
            {record.product_lot_code?.toUpperCase()}
          </Text>
          <Text type="secondary" style={{ fontSize: "12px" }}>
            {record.factory_name}
          </Text>
        </Space>
        <Tag color={getStatusColor(record.status)} style={{ margin: 0 }}>
          {getStatusLabel(record.status, ts)}
        </Tag>
      </Flex>

      <div style={{ height: "1px", background: "rgba(0,0,0,0.06)" }} />

      <Row gutter={[8, 8]}>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {t("grade")}
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
            <Text strong>{record.total_blocks.toLocaleString()}</Text>
          </div>
        </Col>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {tsa("weight")}
            </Text>
            <Text strong>{record.total_weight.toLocaleString()} kg</Text>
          </div>
        </Col>
        <Col span={12}>
          <div style={groupCellStyle}>
            <Text
              type="secondary"
              style={{ fontSize: "12px", display: "block" }}>
              {tc("from_date")}
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
          <TooltipButton
            tooltip={tc("view")}
            type="dashed"
            icon={<EyeOutlined />}
            onClick={() => onView(record)}
          />
          {record?.lot_type !== "external" && (
            <TooltipButton
              tooltip={tc("edit")}
              type="primary"
              ghost
              icon={<EditOutlined />}
              onClick={() => onEdit(record)}
            />
          )}
          {/* <Popconfirm
            title="Xác nhận xóa"
            description={`Bạn có chắc chắn muốn xóa lô sản phẩm "${record.product_lot_code}"?`}
            onConfirm={() => onDelete(record)}
            okText="Xóa"
            cancelText="Hủy"
            okButtonProps={{ danger: true, loading: deleting }}>
            <Button size="small" danger icon={<DeleteOutlined />}>
              Xóa
            </Button>
          </Popconfirm> */}
        </Flex>
      </div>

      {/* <Text
        type="secondary"
        style={{ fontSize: "11px", textAlign: "right", marginTop: "4px" }}>
        Xác nhận:{" "}
        {record.confirmed_at
          ? formatDateDDMMYYYY(record.confirmed_at)
          : "Chưa xác nhận"}
      </Text> */}
    </Card>
  );
};

export default ProductLotCard;
