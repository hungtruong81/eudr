"use client";
import BaseSheet from "@/components/shared/base-sheet";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { handleApiError } from "@/lib/api-error";
import {
  Button,
  Card,
  Col,
  Divider,
  Form,
  Input,
  InputNumber,
  Row,
  Select,
  message,
} from "antd";
import { MinusCircleOutlined, PlusOutlined } from "@ant-design/icons";
import React, { useEffect, useState } from "react";
import {
  createFinishedGoodsReceipt,
  generateCodeFinishedGoodsReceipt,
  getFinishedGoodsReceiptByCode,
  updateFinishedGoodsReceipt,
} from "../actions";
import { getProductionOrders } from "../../product-order/actions";
import { getProductTank } from "../../../factory-metadata/product-tank/actions";
import { getProductTypes } from "../../../factory-metadata/product-type/action";
import { IFinishedGoodsReceipt } from "../types";
import { IProductionOrder } from "../../product-order/types";
import { IProductTank } from "../../../factory-metadata/product-tank/types";
import { IProductType } from "@/features/factory/factory-metadata/product-type/types";
import { useQuery } from "@tanstack/react-query";
import { useTranslations } from "next-intl";

interface IFinishedGoodsReceiptFormProps {
  open: boolean;
  onClose: () => void;
  record: IFinishedGoodsReceipt | null;
  onSuccess: () => void;
}

const FinishedGoodsReceiptForm = ({
  open,
  onClose,
  record,
  onSuccess,
}: IFinishedGoodsReceiptFormProps) => {
  const t = useTranslations("Factory.fg_receipt");
  const tc = useTranslations("Common");
  const tp = useTranslations("Factory.product_order");

  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const category = Form.useWatch("product_type_category", form);
  const actualQuantity = Form.useWatch("actual_quantity", form);
  const rubberBlocks = Form.useWatch("rubber_blocks", form) || [];

  const { data: getFinishedGoodsReceiptByRCode } = useQuery({
    queryKey: ["production-order", record?.finished_goods_receipt_code],
    queryFn: () =>
      getFinishedGoodsReceiptByCode(record?.finished_goods_receipt_code!),
    enabled: !!record?.finished_goods_receipt_code,
    refetchOnWindowFocus: false,
  });

  useEffect(() => {
    if (open) {
      if (record) {
        form.setFieldsValue({
          ...record,
          production_order_id: getFinishedGoodsReceiptByRCode?.data
            .production_order_id
            ? String(getFinishedGoodsReceiptByRCode?.data.production_order_id)
            : undefined,
          product_tank_id: getFinishedGoodsReceiptByRCode?.data.product_tank_id
            ? String(getFinishedGoodsReceiptByRCode?.data.product_tank_id)
            : undefined,
          product_type_id: getFinishedGoodsReceiptByRCode?.data.product_type_id
            ? String(getFinishedGoodsReceiptByRCode?.data.product_type_id)
            : undefined,
          rubber_blocks:
            getFinishedGoodsReceiptByRCode?.data.rubber_blocks?.map(
              (block) => ({
                product_type_id: block.product_type_id
                  ? String(block.product_type_id)
                  : undefined,
                grade: block.grade,
                weight: block.weight,
              }),
            ),
        });
      } else {
        form.resetFields();
        fetchCode();
      }
    }
  }, [open, record, getFinishedGoodsReceiptByRCode, form]);

  const fetchCode = async () => {
    try {
      const res = await generateCodeFinishedGoodsReceipt();
      if (
        res?.data?.finished_goods_receipt_code ||
        res?.finished_goods_receipt_code
      ) {
        form.setFieldValue(
          "finished_goods_receipt_code",
          res.data?.finished_goods_receipt_code ||
            res.finished_goods_receipt_code,
        );
      }
    } catch (error) {
      console.error("Lỗi khi lấy mã sản xuất:", error);
    }
  };

  const handleFinish = async (values: any) => {
    try {
      setLoading(true);
      const payload = {
        ...values,
        production_order_id: Number(values.production_order_id),
        product_tank_id: Number(values.product_tank_id),
        product_type_id: Number(values.product_type_id),
        rubber_blocks: values.rubber_blocks?.map((block: any) => ({
          ...block,
          product_type_id: Number(block.product_type_id),
          weight: Number(block.weight),
          quantity: Number(block.quantity),
        })),
      };

      if (record) {
        await updateFinishedGoodsReceipt(
          record.finished_goods_receipt_code,
          payload,
        );
        message.success(t("update_success"));
      } else {
        await createFinishedGoodsReceipt(payload);
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

  const totalBaleWeight = rubberBlocks.reduce((total: number, block: any) => {
    const weight = Number(block?.weight) || 0;
    const quantity = Number(block?.quantity) || 0;
    return total + weight * quantity;
  }, 0);

  const isWeightMatch = actualQuantity === totalBaleWeight;

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={form.submit}
      title={record ? t("view_title") : t("add_title")}
      loading={loading}>
      <Form form={form} layout="vertical" onFinish={handleFinish}>
        <Row gutter={16}>
          <Col span={24}>
            <Form.Item
              label={t("name")}
              name="finished_goods_receipt_name"
              rules={[{ required: true, message: t("enter_name") }]}>
              <Input placeholder={t("enter_name")} />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item
              label={t("code")}
              name="finished_goods_receipt_code"
              rules={[{ required: true, message: t("enter_code") }]}>
              <Input
                placeholder={tc("auto_generate")}
                className="uppercase"
                disabled
              />
            </Form.Item>
          </Col>

          <Col span={24}>
            <Form.Item
              label={tp("title")}
              name="production_order_id"
              rules={[{ required: true, message: t("select_order") }]}>
              <InfiniteScrollSelect<IProductionOrder>
                queryKey={["production-orders-in-production"]}
                fetchFn={(p) =>
                  getProductionOrders({ ...p, status: "in_production" })
                }
                mapOption={(item) => ({
                  label: `${item.production_order_name}`,
                  value: String(item.production_order_id),
                })}
                placeholder={t("select_order")}
                initialOptions={
                  record?.production_order_id
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

          <Col span={12}>
            <Form.Item
              label={tp("product_category")}
              name="product_type_category"
              rules={[{ required: true, message: tc("select_category") }]}>
              <Select
                placeholder={tc("select_category")}
                options={[
                  {
                    label: tp("concentrated_latex_label"),
                    value: "concentrated_latex",
                  },
                  { label: "Mủ tạp", value: "scrap_rubber" },
                ]}
                onChange={() => {
                  form.setFieldValue("product_type_id", null);
                }}
              />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              label={tp("product")}
              name="product_type_id"
              rules={[{ required: true, message: tp("select_product") }]}>
              <InfiniteScrollSelect
                queryKey={["product-types-select", category]}
                fetchFn={(p) =>
                  getProductTypes({ ...p, product_type_category: category })
                }
                mapOption={(item: any) => ({
                  label: item.product_type_name,
                  value: String(item.product_type_id),
                })}
                placeholder={tp("select_product")}
                disabled={!category}
                initialOptions={
                  record?.product_type_id
                    ? [
                        {
                          label: record.product_type_name,
                          value: String(record.product_type_id),
                        },
                      ]
                    : []
                }
              />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              label={t("tank")}
              name="product_tank_id"
              rules={[{ required: true, message: tc("select_location") }]}>
              <InfiniteScrollSelect<IProductTank>
                queryKey={["product-tanks-select"]}
                fetchFn={getProductTank}
                mapOption={(item) => ({
                  label: `${item.product_tank_name} (${item.capacity}kg)`,
                  value: String(item.product_tank_id),
                })}
                placeholder={tc("select_location")}
                initialOptions={
                  record?.product_tank_id
                    ? [
                        {
                          label: record.product_tank_name,
                          value: String(record.product_tank_id),
                        },
                      ]
                    : []
                }
              />
            </Form.Item>
          </Col>

          <Col span={12}>
            <Form.Item
              label={t("actual_quantity")}
              name="actual_quantity"
              rules={[{ required: true, message: tc("enter_quantity") }]}>
              <InputNumber
                style={{ width: "100%" }}
                min={0}
                placeholder={tc("enter_quantity")}
              />
            </Form.Item>
          </Col>
        </Row>

        <>
          <Divider titlePlacement="left">
            {t("lot_list")}
            {actualQuantity > 0 && (
              <span
                style={{
                  marginLeft: 16,
                  fontSize: 14,
                  color: isWeightMatch ? "#52c41a" : "#ff4d4f",
                }}>
                ({t("allocated")}: {totalBaleWeight.toLocaleString()} /{" "}
                {actualQuantity.toLocaleString()} kg)
              </span>
            )}
          </Divider>
          <Form.List
            name="rubber_blocks"
            rules={[
              {
                validator: async (_, blocks) => {
                  if (!blocks || blocks.length === 0) return;
                  const total = blocks.reduce((acc: number, block: any) => {
                    return (
                      acc +
                      (Number(block?.weight) || 0) *
                        (Number(block?.quantity) || 0)
                    );
                  }, 0);
                  if (total !== actualQuantity) {
                    return Promise.reject(
                      new Error(
                        t("weight_unmatch_error", {
                          total,
                          actual: actualQuantity,
                        }),
                      ),
                    );
                  }
                },
              },
            ]}>
            {(fields, { add, remove }, { errors }) => (
              <>
                <Form.ErrorList errors={errors} />
                {fields.map(({ key, name, ...restField }) => (
                  <Card
                    key={key}
                    size="small"
                    style={{ marginBottom: 16 }}
                    extra={
                      <MinusCircleOutlined
                        onClick={() => remove(name)}
                        style={{ color: "red" }}
                      />
                    }>
                    <Row gutter={16}>
                      <Col span={12}>
                        <Form.Item
                          {...restField}
                          label={t("bale_type")}
                          name={[name, "product_type_id"]}
                          rules={[
                            { required: true, message: tc("select_type") },
                          ]}>
                          <InfiniteScrollSelect
                            queryKey={["product-types-select", "scrap_rubber"]}
                            fetchFn={(p) =>
                              getProductTypes({
                                ...p,
                              })
                            }
                            mapOption={(item: IProductType) => ({
                              label: item.product_type_name,
                              value: String(item.product_type_id),
                              record: item,
                            })}
                            placeholder={tc("select_type")}
                            initialOptions={
                              record?.rubber_blocks?.[name]
                                ? [
                                    {
                                      label:
                                        record.rubber_blocks[name]
                                          .product_type_name,
                                      value: String(
                                        record.rubber_blocks[name]
                                          .product_type_id,
                                      ),
                                    },
                                  ]
                                : []
                            }
                            onSelect={(_, option: any) => {
                              form.setFieldValue(
                                ["rubber_blocks", name, "weight"],
                                Number(option.record?.product_weight) || 0,
                              );
                            }}
                          />
                        </Form.Item>
                      </Col>
                      <Col span={12}>
                        <Form.Item
                          {...restField}
                          label={tc("grade")}
                          name={[name, "grade"]}
                          rules={[
                            { required: true, message: tc("enter_grade") },
                          ]}>
                          <Input placeholder="VD: SVR 3L" />
                        </Form.Item>
                      </Col>
                      <Col span={12}>
                        <Form.Item
                          {...restField}
                          label={tc("weight_kg")}
                          name={[name, "weight"]}
                          rules={[
                            { required: true, message: tc("enter_weight") },
                          ]}>
                          <InputNumber
                            style={{ width: "100%" }}
                            min={0}
                            disabled
                            placeholder={tc("enter_weight")}
                          />
                        </Form.Item>
                      </Col>
                      <Col span={12}>
                        <Form.Item
                          {...restField}
                          label={tc("quantity")}
                          name={[name, "quantity"]}
                          rules={[
                            { required: true, message: tc("enter_quantity") },
                          ]}>
                          <InputNumber
                            style={{ width: "100%" }}
                            min={0}
                            placeholder={tc("enter_quantity")}
                          />
                        </Form.Item>
                      </Col>
                    </Row>
                  </Card>
                ))}
                <Form.Item>
                  <Button
                    type="dashed"
                    onClick={() => add()}
                    block
                    icon={<PlusOutlined />}>
                    {t("add_lot")}
                  </Button>
                </Form.Item>
              </>
            )}
          </Form.List>
        </>
      </Form>
    </BaseSheet>
  );
};

export default FinishedGoodsReceiptForm;
