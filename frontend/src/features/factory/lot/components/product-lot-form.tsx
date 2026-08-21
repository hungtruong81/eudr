"use client";
import BaseSheet from "@/components/shared/base-sheet";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { handleApiError } from "@/lib/api-error";
import { Col, Form, Row, message } from "antd";
import React, { useCallback, useEffect, useState } from "react";
import { useTranslations } from "next-intl";
import {
  createProductLot,
  getProductLotByCode,
  updateProductLot,
} from "../actions";
import { getFactory } from "../../factory-metadata/factory/actions";
import { getProductionOrders } from "../../manage-order-ticket/product-order/actions";
import { getFgReceiptSummary } from "../../fg-receipt-summary/actions";
import { IProductLot } from "../types";
import { IFactory } from "../../factory-metadata/factory/types";
import { IProductionOrder } from "../../manage-order-ticket/product-order/types";
import { IFgReceiptSummary } from "../../fg-receipt-summary/types";
import { useQuery } from "@tanstack/react-query";

interface IProductLotFormProps {
  open: boolean;
  onClose: () => void;
  record: IProductLot | null;
  onSuccess: () => void;
}

const ProductLotForm = ({
  open,
  onClose,
  record,
  onSuccess,
}: IProductLotFormProps) => {
  const t = useTranslations("Factory");
  const form = Form.useForm()[0];
  const [loading, setLoading] = useState(false);
  const factoryId = Form.useWatch("factory_id", form);
  const productionOrderId = Form.useWatch("production_order_id", form);

  const { data: productLot } = useQuery({
    queryKey: ["product-lot", record?.product_lot_code],
    queryFn: () => getProductLotByCode(record?.product_lot_code || ""),
    enabled: !!record?.product_lot_code,
  });

  useEffect(() => {
    if (open) {
      if (record) {
        form.setFieldsValue({
          ...productLot?.data,
          production_order_id: String(
            productLot?.data.items[0]?.production_order_id || "",
          ),
          rubber_block_ids:
            productLot?.data.items.map((item) =>
              String(item.rubber_block_id),
            ) || [],
          factory_id: String(productLot?.data.factory_id),
        });
      } else {
        form.resetFields();
      }
    }
  }, [open, record, form, productLot]);

  const handleFinish = async (values: any) => {
    try {
      setLoading(true);
      const payload = {
        ...values,
        factory_id: Number(values.factory_id),
        production_order_id: Number(values.production_order_id),
        rubber_block_ids: values.rubber_block_ids?.map(Number) || [],
      };

      if (record) {
        await updateProductLot(record.product_lot_code, payload);
        message.success(t("update_success"));
      } else {
        await createProductLot(payload);
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
      title={record ? t("lot_detail") : t("add_lot")}
      loading={loading}>
      <Form form={form} layout="vertical" onFinish={handleFinish}>
        <Row gutter={16}>
          <Col span={24}>
            <Form.Item
              label={t("factory_name")}
              name="factory_id"
              rules={[{ required: true, message: t("select_factory_error") }]}>
              <InfiniteScrollSelect<IFactory>
                queryKey={["factories-select"]}
                fetchFn={getFactory}
                mapOption={(item) => ({
                  label: item.factory_name,
                  value: String(item.factory_id),
                })}
                //       ]
                //     : []
                // }
                placeholder={t("select_factory")}
              />
            </Form.Item>
          </Col>

          <Col span={24}>
            <Form.Item
              label={t("production_order")}
              name="production_order_id"
              rules={[
                { required: true, message: t("select_production_order_error") },
              ]}>
              <InfiniteScrollSelect<IProductionOrder>
                queryKey={["production-orders-select", factoryId]}
                fetchFn={(p) => getProductionOrders({ ...p })}
                mapOption={(item) => ({
                  label: item.production_order_name,
                  value: String(item.production_order_id),
                })}
                placeholder={t("select_production_order")}
                disabled={!factoryId}
              />
            </Form.Item>
          </Col>

          <Col span={24}>
            <Form.Item
              label={t("rubber_block_list")}
              name="rubber_block_ids"
              rules={[
                { required: true, message: t("select_rubber_block_error") },
              ]}>
              <InfiniteScrollSelect<IFgReceiptSummary>
                mode="multiple"
                queryKey={["rubber-blocks-select", productionOrderId]}
                fetchFn={(p) =>
                  getFgReceiptSummary({
                    ...p,
                    production_order_id: Number(productionOrderId),
                    status: "available",
                  })
                }
                mapOption={(item) => ({
                  label: `${item.rubber_block_code} (${item.weight}kg) - ${item.grade}`,
                  value: String(item.rubber_block_id),
                })}
                initialOptions={productLot?.data?.items?.map((item) => ({
                  label: `${item.rubber_block_code} (${item.weight_snapshot}kg) - ${item.grade_snapshot}`,
                  value: String(item.rubber_block_id),
                }))}
                placeholder={t("select_rubber_block")}
                disabled={!productionOrderId}
                maxCount={50}
              />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </BaseSheet>
  );
};

export default ProductLotForm;
