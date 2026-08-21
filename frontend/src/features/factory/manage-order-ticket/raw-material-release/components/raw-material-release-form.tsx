"use client";
import BaseSheet from "@/components/shared/base-sheet";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { handleApiError } from "@/lib/api-error";
import {
  Button,
  Col,
  Form,
  Input,
  InputNumber,
  Row,
  Typography,
  message,
  Card,
} from "antd";
import React, { useCallback, useEffect, useState } from "react";
import {
  createRawMaterialRelease,
  generateCodeRawMaterialRelease,
  updateRawMaterialRelease,
} from "../actions";
import { getProductionOrders } from "../../product-order/actions";
import { getRawMaterialTank } from "../../../factory-metadata/raw-material-tank/actions";
import { IRawMaterialReleaseByCode } from "../types";
import { PlusOutlined, DeleteOutlined } from "@ant-design/icons";
import { IProductionOrder } from "../../product-order/types";
import { useTranslations } from "next-intl";

const { Text } = Typography;

interface IRawMaterialReleaseFormProps {
  open: boolean;
  onClose: () => void;
  record: IRawMaterialReleaseByCode | null;
  onSuccess: () => void;
}

const RawMaterialReleaseForm = ({
  open,
  onClose,
  record,
  onSuccess,
}: IRawMaterialReleaseFormProps) => {
  const t = useTranslations("Factory.material_release");
  const tc = useTranslations("Common");
  const tp = useTranslations("Factory.product_order");
  const tm = useTranslations("Factory.metadata.raw_material_tank");

  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);

  const fetchNextCode = useCallback(async () => {
    try {
      const res = await generateCodeRawMaterialRelease();
      const code =
        res?.data?.raw_material_release_code || res?.raw_material_release_code;
      if (code) {
        form.setFieldValue("material_release_code", code);
      }
    } catch (error) {
      console.error("Failed to fetch next code", error);
    }
  }, [form]);

  useEffect(() => {
    if (open) {
      if (record) {
        form.setFieldsValue({
          ...record,
          production_order_id: record.production_order_id
            ? String(record.production_order_id)
            : undefined,
          raw_material_tanks: record.raw_material_tanks.map((item) => ({
            tank_id: String(item.raw_material_tank_id),
            weight_requested: Number(item.weight_requested),
            rubber_type: item.rubber_type,
            notes: item.notes,
          })),
        });
      } else {
        form.resetFields();
        fetchNextCode();
      }
    }
  }, [open, record, form, fetchNextCode]);

  const handleFinish = async (values: any) => {
    try {
      setLoading(true);
      const payload = {
        ...values,
        production_order_id: Number(values.production_order_id) || 0,
        raw_material_tanks: (values.raw_material_tanks || []).map((t: any) => ({
          ...t,
          tank_id: Number(t.tank_id),
          weight_requested: Number(t.weight_requested),
        })),
      };

      if (record) {
        await updateRawMaterialRelease(record.material_release_code, payload);
        message.success(t("update_success"));
      } else {
        await createRawMaterialRelease(payload);
        message.success(t("create_success"));
      }
      onSuccess();
      onClose();
    } catch (error) {
      handleApiError(error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={form.submit}
      title={record ? t("edit_title") : t("add_title")}
      loading={loading}
      width={700}>
      <Form form={form} layout="vertical" onFinish={handleFinish}>
        <Row gutter={16}>
          <Col span={24}>
            <Form.Item
              label={t("name")}
              name="material_release_name"
              rules={[{ required: true, message: t("enter_name") }]}>
              <Input placeholder={t("enter_name")} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              label={t("code")}
              name="material_release_code"
              rules={[{ required: true, message: t("enter_code") }]}>
              <Input
                disabled
                placeholder={tc("auto_generate")}
                className="uppercase"
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item label={tp("title")} name="production_order_id">
              <InfiniteScrollSelect
                queryKey={["production-orders-select"]}
                fetchFn={getProductionOrders}
                mapOption={(item: IProductionOrder) => ({
                  label: item.production_order_name,
                  value: String(item.production_order_id),
                })}
                placeholder={t("select_order")}
                allowClear
                initialOptions={
                  record
                    ? [
                        {
                          label: record.production_order_name,
                          value: String(record.production_order_id),
                        },
                      ]
                    : []
                }
              />
            </Form.Item>
          </Col>
        </Row>

        <div style={{ marginTop: 16 }}>
          <Text strong>{t("tank_list")}</Text>
          <Form.List
            name="raw_material_tanks"
            rules={[
              {
                validator: async (_, names) => {
                  if (!names || names.length < 1) {
                    return Promise.reject(
                      new Error(t("at_least_one_tank_error")),
                    );
                  }
                },
              },
            ]}>
            {(fields, { add, remove }, { errors }) => (
              <div style={{ marginTop: 8 }}>
                {fields.map(({ key, name, ...restField }) => (
                  <Card
                    key={key}
                    size="small"
                    style={{ marginBottom: 16 }}
                    extra={
                      <Button
                        type="text"
                        danger
                        icon={<DeleteOutlined />}
                        onClick={() => remove(name)}
                      />
                    }>
                    <Row gutter={16}>
                      <Col span={12}>
                        <Form.Item
                          {...restField}
                          name={[name, "tank_id"]}
                          label={tm("title")}
                          rules={[
                            { required: true, message: t("select_tank") },
                          ]}>
                          <InfiniteScrollSelect
                            queryKey={["raw-material-tanks-select"]}
                            fetchFn={getRawMaterialTank}
                            mapOption={(item) => ({
                              label: `${item.raw_material_tank_name} (${item.current_volume}kg)`,
                              value: String(item.raw_material_tank_id),
                              item,
                            })}
                            placeholder={t("select_tank")}
                            onChange={(val, option: any) => {
                              // Optionally fill rubber_type if available
                              if (option?.item) {
                                form.setFieldValue(
                                  ["raw_material_tanks", name, "rubber_type"],
                                  option.item.tank_type,
                                );
                              }
                            }}
                            initialOptions={
                              record?.raw_material_tanks?.[name]
                                ? [
                                    {
                                      label:
                                        record.raw_material_tanks[name]
                                          .raw_material_tank_name,
                                      value: String(
                                        record.raw_material_tanks[name]
                                          .raw_material_tank_id,
                                      ),
                                    },
                                  ]
                                : []
                            }
                          />
                        </Form.Item>
                      </Col>
                      <Col span={12}>
                        <Form.Item
                          {...restField}
                          name={[name, "weight_requested"]}
                          label={tc("weight_requested")}
                          rules={[
                            { required: true, message: tc("enter_weight") },
                          ]}>
                          <InputNumber
                            style={{ width: "100%" }}
                            min={0}
                            placeholder={tc("enter_weight")}
                          />
                        </Form.Item>
                      </Col>
                      <Col span={12}>
                        <Form.Item
                          {...restField}
                          name={[name, "rubber_type"]}
                          label={tc("rubber_type")}
                          rules={[
                            { required: true, message: tc("enter_type") },
                          ]}>
                          <Input placeholder={tc("rubber_type")} />
                        </Form.Item>
                      </Col>
                      <Col span={12}>
                        <Form.Item
                          {...restField}
                          name={[name, "notes"]}
                          label={tc("notes")}>
                          <Input placeholder={tc("notes")} />
                        </Form.Item>
                      </Col>
                    </Row>
                  </Card>
                ))}
                <Button
                  type="dashed"
                  onClick={() => add()}
                  block
                  icon={<PlusOutlined />}
                  style={{ marginBottom: 16 }}>
                  {tc("add")}
                </Button>
                <Form.ErrorList errors={errors} />
              </div>
            )}
          </Form.List>
        </div>
      </Form>
    </BaseSheet>
  );
};

export default RawMaterialReleaseForm;
