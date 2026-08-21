"use client";
import {
  Alert,
  Button,
  Col,
  Form,
  InputNumber,
  Row,
  Space,
  Table,
  Typography,
  message,
} from "antd";
import { ITransportationRouteDetail } from "@/features/route/transportation-route/types";
import { useEffect, useState } from "react";
import { IRawMaterialTank } from "@/features/factory/factory-metadata/raw-material-tank/types";
import { useMutation } from "@tanstack/react-query";
import { transportationRouteUnload } from "@/features/factory/receive-material/actions";
import { handleApiError } from "@/lib/api-error";
import BaseSheet from "@/components/shared/base-sheet";
import { useTranslations } from "next-intl";

const { Title, Text } = Typography;

interface UnloadAllocationModalProps {
  open: boolean;
  onClose: () => void;
  transportationRoute: ITransportationRouteDetail | null;
  selectedTanks: IRawMaterialTank[];
  onSuccess?: () => void;
}

const UnloadAllocationModal = ({
  open,
  onClose,
  transportationRoute,
  selectedTanks,
  onSuccess,
}: UnloadAllocationModalProps) => {
  const t = useTranslations("Factory.receive_material");
  const tc = useTranslations("Common");
  const tf = useTranslations("Factory.fg_receipt");

  const [form] = Form.useForm();
  const [allocatedWeight, setAllocatedWeight] = useState(0);

  const unloadMutation = useMutation({
    mutationFn: (data: any) =>
      transportationRouteUnload(
        transportationRoute?.transportation_route_code || "",
        data,
      ),
    onSuccess: () => {
      message.success(t("unload_success"));
      onSuccess?.();
      onClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  // Calculate totals from transaction tickets
  const totalLatex =
    transportationRoute?.source_transaction_tickets?.reduce(
      (acc, ticket) => acc + Number(ticket.latex_weight || 0),
      0,
    ) || 0;
  const totalScrap =
    transportationRoute?.source_transaction_tickets?.reduce(
      (acc, ticket) => acc + Number(ticket.scrap_rubber_weight || 0),
      0,
    ) || 0;
  const totalWeight = totalLatex + totalScrap;

  const remainingWeight = totalWeight - allocatedWeight;

  useEffect(() => {
    if (open) {
      form.resetFields();
      setAllocatedWeight(0);
    }
  }, [open, form]);

  const onValuesChange = (_: any, allValues: any) => {
    const total = Object.values(allValues.allocations || {}).reduce(
      (acc: number, val: any) => acc + (Number(val) || 0),
      0,
    );
    setAllocatedWeight(total as number);
  };

  const fillRemaining = (tankId: number) => {
    const currentAllocations = form.getFieldValue("allocations") || {};
    const otherAllocationsTotal = Object.entries(currentAllocations).reduce(
      (acc: number, [id, val]) =>
        Number(id) !== tankId ? acc + (Number(val) || 0) : acc,
      0,
    );
    const newAllocation = totalWeight - otherAllocationsTotal;

    form.setFieldValue(["allocations", tankId], Math.max(0, newAllocation));
    onValuesChange(null, form.getFieldsValue());
  };

  const onFinish = (values: any) => {
    if (allocatedWeight !== totalWeight) {
      message.warning(t("alloc_finish_warning"));
      return;
    }

    const unloading_items = Object.entries(values.allocations)
      .filter(([_, weight]) => Number(weight) > 0)
      .map(([tankId, weight]) => {
        const tank = selectedTanks.find(
          (t) => t.raw_material_tank_id === Number(tankId),
        );
        return {
          raw_material_tank_id: Number(tankId),
          rubber_type: tank?.tank_type === "latex" ? "latex" : "scrap_rubber",
          actual_weight: String(weight),
        };
      });

    unloadMutation.mutate({ unloading_items });
  };

  const detailColumns = [
    {
      title: tc("info"),
      dataIndex: "label",
      key: "label",
      width: "45%",
      render: (text: string) => <Text type="secondary">{text}</Text>,
    },
    {
      title: tc("detail"),
      dataIndex: "value",
      key: "value",
      render: (text: any) => <Text strong>{text ?? "—"}</Text>,
    },
  ];

  const tankColumns = [
    {
      title: tc("tank_name"),
      dataIndex: "raw_material_tank_name",
      key: "name",
    },
    {
      title: tc("rubber_type"),
      dataIndex: "tank_type",
      key: "type",
      render: (val: string) => {
        const types: Record<string, string> = {
          latex: tc("latex"),
          scrap_rubber: tc("scrap_rubber"),
          mixed: tc("mixed"),
        };
        return types[val] || val;
      },
    },
    {
      title: tc("status_kg"),
      key: "status",
      render: (_: any, record: IRawMaterialTank) =>
        `${record.current_volume}/${record.capacity}`,
    },
    {
      title: t("alloc_weight"),
      key: "allocation",
      render: (_: any, record: IRawMaterialTank) => (
        <Space>
          <Form.Item
            name={["allocations", record.raw_material_tank_id]}
            noStyle
            rules={[
              { required: true, message: tc("enter_weight") },
              { type: "number", min: 0, message: tc("invalid_number") },
            ]}>
            <InputNumber style={{ width: 150 }} placeholder={tc("enter_weight")} />
          </Form.Item>
          <Button
            size="small"
            onClick={() => fillRemaining(record.raw_material_tank_id)}>
            {t("alloc_all_remaining")}
          </Button>
        </Space>
      ),
    },
  ];

  return (
    <BaseSheet
      title={t("unload_allocation_title")}
      open={open}
      onClose={onClose}
      onOk={() => form.submit()}
      width={1000}
      okText={t("unload_to_tank_tooltip")}
      cancelText={tc("cancel")}
      loading={unloadMutation.isPending}>
      <Space orientation="vertical" style={{ width: "100%" }} size="large">
        <Row gutter={24}>
          <Col span={12}>
            <Table
              title={() => <Text strong>{t("total_summary")}</Text>}
              dataSource={[
                { label: t("total_weight"), value: `${totalWeight} kg` },
                { label: tc("latex"), value: `${totalLatex} kg` },
                { label: tc("scrap_rubber"), value: `${totalScrap} kg` },
              ]}
              columns={detailColumns}
              pagination={false}
              size="small"
              bordered
              rowKey="label"
            />
          </Col>
          <Col span={12}>
            <Table
              title={() => <Text strong>{t("alloc_status")}</Text>}
              dataSource={[
                {
                  label: t("allocated"),
                  value: (
                    <Text
                      type={
                        allocatedWeight > totalWeight ? "danger" : "success"
                      }
                      strong>
                      {allocatedWeight} kg
                    </Text>
                  ),
                },
                {
                  label: t("remaining"),
                  value: (
                    <Text
                      type={remainingWeight < 0 ? "danger" : undefined}
                      strong>
                      {remainingWeight} kg
                    </Text>
                  ),
                },
              ]}
              columns={detailColumns}
              pagination={false}
              size="small"
              bordered
              rowKey="label"
            />
          </Col>
        </Row>

        {allocatedWeight > totalWeight && (
          <Alert
            description={t("alloc_exceed_warning")}
            type="error"
            showIcon
          />
        )}

        <div>
          <Title level={5}>
            {t("purchase_tickets_list")} ({tc("route")}{" "}
            {transportationRoute?.transportation_route_code?.toUpperCase()})
          </Title>
          <Table
            dataSource={transportationRoute?.source_transaction_tickets}
            rowKey="transaction_ticket_id"
            pagination={false}
            size="small"
            columns={[
              { title: tc("contract"), dataIndex: "contract_code" },
              { title: t("seller"), dataIndex: "seller_name" },
              { title: `${tc("latex")} (kg)`, dataIndex: "latex_weight" },
              { title: `${tc("scrap_rubber")} (kg)`, dataIndex: "scrap_rubber_weight" },
            ]}
          />
        </div>

        <div>
          <Title level={5}>{t("allocation_detail")}</Title>
          <Form form={form} onValuesChange={onValuesChange} onFinish={onFinish}>
            <Table
              dataSource={selectedTanks}
              columns={tankColumns}
              rowKey="raw_material_tank_id"
              pagination={false}
              bordered
            />
          </Form>
        </div>
      </Space>
    </BaseSheet>
  );
};

export default UnloadAllocationModal;
