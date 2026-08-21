"use client";

import React, { useEffect, useState } from "react";
import { Button, Form, message, Space, Steps } from "antd";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import dayjs from "dayjs";
import BaseSheet from "@/components/shared/base-sheet";
import { handleApiError } from "@/lib/api-error";
import { createExternalProductLot, updateExternalProductLot } from "../actions";
import { IExternalProductLotData, IProductLotExternal } from "../types";
import StepPlots from "./steps/step-plots";
import StepGeneralInfo from "./steps/step-general-info";
import StepTransport from "./steps/step-transport";
import { useTranslations } from "next-intl";

interface Props {
  open: boolean;
  onClose: () => void;
  record: IProductLotExternal | null;
  onSuccess: () => void;
}

const ExternalProductLotForm = ({
  open,
  onClose,
  record,
  onSuccess,
}: Props) => {
  const t = useTranslations("Sales.external_product_lot");
  const tCommon = useTranslations("Common");

  const [form] = Form.useForm();
  const [current, setCurrent] = useState(0);
  const queryClient = useQueryClient();

  useEffect(() => {
    if (open) {
      if (record) {
        form.setFieldsValue({
          ...record,
          production_date_range: [
            dayjs(record.production_date_from),
            dayjs(record.production_date_to),
          ],
          purchase_date: dayjs(record.purchase_date),
          transport: {
            ...record.transport,
            transport_date: dayjs(record?.transport?.transport_date),
            pickup_time: dayjs(record?.transport?.pickup_time, "HH:mm"),
            delivery_time: dayjs(record?.transport?.delivery_time, "HH:mm"),
          },
        });
      } else {
        form.resetFields();
        form.setFieldsValue({
          lands: [{}],
        });
      }
      setCurrent(0);
    }
  }, [open, record, form]);

  const mutateCreate = useMutation({
    mutationFn: (data: IExternalProductLotData) =>
      createExternalProductLot(data),
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["external-product-lots"] });
      onSuccess();
      onClose();
    },
    onError: (error) => handleApiError(error),
  });

  const mutateUpdate = useMutation({
    mutationFn: ({ id, data }: { id: string; data: IExternalProductLotData }) =>
      updateExternalProductLot(id, data),
    onSuccess: () => {
      message.success(tCommon("update_success"));
      queryClient.invalidateQueries({ queryKey: ["external-product-lots"] });
      onSuccess();
      onClose();
    },
    onError: (error) => handleApiError(error),
  });

  const next = async () => {
    try {
      if (current === 0) {
        await form.validateFields(["lands"]);
      } else if (current === 1) {
        await form.validateFields([
          "supplier_company_name",
          "supplier_phone",
          "factory_id",
          "grade",
          "total_blocks",
          "total_weight",
          "production_date_range",
          "purchase_date",
          "purchase_amount",
          "supplier_factory_name",
          "supplier_address",
          "original_product_lot_code",
        ]);
      }
      setCurrent(current + 1);
    } catch (e) {
      console.log("Validation failed:", e);
    }
  };

  const prev = () => setCurrent(current - 1);

  const onFinish = async () => {
    try {
      const values = await form.validateFields();

      const payload: IExternalProductLotData = {
        ...values,
        production_date_from:
          values.production_date_range[0].format("YYYY-MM-DD"),
        production_date_to:
          values.production_date_range[1].format("YYYY-MM-DD"),
        purchase_date: values.purchase_date.format("YYYY-MM-DD"),
        transport: {
          ...values.transport,
          transport_date: values.transport.transport_date.format("YYYY-MM-DD"),
          pickup_time: values.transport.pickup_time.format("HH:mm"),
          delivery_time: values.transport.delivery_time.format("HH:mm"),
        },
      };

      if (record?.product_lot_code) {
        mutateUpdate.mutate({
          id: record.product_lot_code,
          data: payload,
        });
      } else {
        mutateCreate.mutate(payload);
      }
    } catch (e) {
      message.warning(tCommon("check_info_warning"));
    }
  };

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      title={
        record ? t("update_title") : t("create_title")
      }
      width={1000}
      extra={
        <Space>
          <Button onClick={onClose}>{tCommon("cancel")}</Button>
          {current > 0 && <Button onClick={prev}>{tCommon("back")}</Button>}
          {current < 2 ? (
            <Button type="primary" onClick={next}>
              {tCommon("next")}
            </Button>
          ) : (
            <Button
              type="primary"
              onClick={onFinish}
              loading={mutateCreate.isPending || mutateUpdate.isPending}>
              {tCommon("save")}
            </Button>
          )}
        </Space>
      }>
      <Steps
        current={current}
        items={[
          { title: t("plots") },
          { title: t("general_info") },
          { title: t("transport") },
        ]}
        style={{ marginBottom: 24 }}
      />

      <Form form={form} layout="vertical" preserve={true}>
        <div style={{ display: current === 0 ? "block" : "none" }}>
          <StepPlots />
        </div>
        <div style={{ display: current === 1 ? "block" : "none" }}>
          <StepGeneralInfo />
        </div>
        <div style={{ display: current === 2 ? "block" : "none" }}>
          <StepTransport />
        </div>
      </Form>
    </BaseSheet>
  );
};

export default ExternalProductLotForm;
