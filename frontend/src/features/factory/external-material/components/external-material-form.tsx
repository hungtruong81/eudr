"use client";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Button, Form, Space, Steps, message } from "antd";
import React, { useEffect, useState } from "react";
import dayjs from "dayjs";
import {
  createExternalMaterial,
  getExternalMaterialByCode,
  updateExternalMaterial,
} from "../actions";
import { IExternalMaterial, IExternalMaterialData } from "../types";
import StepGeneralInfo from "./steps/step-general-info";
import StepPlots from "./steps/step-plots";
import StepTransport from "./steps/step-transport";
import { handleApiError } from "@/lib/api-error";
import BaseSheet from "@/components/shared/base-sheet";
import { useTranslations } from "next-intl";

interface Props {
  open: boolean;
  onClose: () => void;
  record: IExternalMaterial | null;
}

const ExternalMaterialForm = ({ open, onClose, record }: Props) => {
  const t = useTranslations("Factory.external_material");
  const tc = useTranslations("Common");
  const [form] = Form.useForm();
  const [current, setCurrent] = useState(0);
  const queryClient = useQueryClient();

  const { data: response, isLoading } = useQuery({
    queryKey: ["external-material-detail", record?.external_material_code],
    queryFn: () => getExternalMaterialByCode(record?.external_material_code!),
    enabled: !!record?.external_material_code && open,
  });

  useEffect(() => {
    if (open) {
      if (response?.data) {
        form.setFieldsValue({
          ...response?.data,
          plots: response?.data.lands.map((plot) => ({
            ...plot,
            province_id: plot.province_id,
            coordinates: plot.coordinates.map((coord) => ({
              lat: coord.lat,
              lng: coord.lng,
            })),
          })),
          purchase_date: response?.data.purchase_date
            ? dayjs(response?.data.purchase_date)
            : undefined,
          transport: {
            ...response?.data.transport,
            transport_date: response?.data.transport?.transport_date
              ? dayjs(response?.data.transport.transport_date)
              : undefined,
            pickup_time: response?.data.transport?.pickup_time
              ? dayjs(response?.data.transport.pickup_time, "HH:mm")
              : undefined,
            delivery_time: response?.data.transport?.delivery_time
              ? dayjs(response?.data.transport.delivery_time, "HH:mm")
              : undefined,
          },
        });
      } else {
        form.resetFields();
        form.setFieldsValue({
          plots: [{ coordinates: [] }],
        });
      }
      setCurrent(0);
    }
  }, [open, response, form]);

  const mutateCreate = useMutation({
    mutationFn: (data: IExternalMaterialData) => createExternalMaterial(data),
    onSuccess: () => {
      message.success(t("add_success"));
      queryClient.invalidateQueries({ queryKey: ["external-material"] });
      onClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const mutateUpdate = useMutation({
    mutationFn: ({
      external_material_code,
      data,
    }: {
      external_material_code: string;
      data: IExternalMaterialData;
    }) => updateExternalMaterial(external_material_code, data),
    onSuccess: () => {
      message.success(t("update_form_success"));
      queryClient.invalidateQueries({ queryKey: ["external-material"] });
      onClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const next = async () => {
    try {
      if (current === 0) {
        const plots = form.getFieldValue("plots") || [];
        if (plots.length === 0) {
          message.error(t("plot_required_error"));
          return;
        }

        const pathsToValidate: any[] = [];
        plots.forEach((plot: any, index: number) => {
          pathsToValidate.push(["plots", index, "plot_name"]);
          pathsToValidate.push(["plots", index, "province_id"]);
          pathsToValidate.push(["plots", index, "land_area"]);
          pathsToValidate.push(["plots", index, "harvest_weight"]);
          pathsToValidate.push(["plots", index, "address"]);
          
          if (plot?.coordinates && Array.isArray(plot.coordinates)) {
            plot.coordinates.forEach((_: any, cIndex: number) => {
              pathsToValidate.push(["plots", index, "coordinates", cIndex, "lat"]);
              pathsToValidate.push(["plots", index, "coordinates", cIndex, "lng"]);
            });
          }
        });

        await form.validateFields(pathsToValidate);
      } else if (current === 1) {
        await form.validateFields([
          "factory_id",
          "supplier_name",
          "supplier_phone",
          "supplier_address",
          "purchase_date",
          "latex_weight",
          "latex_tsc_grade",
          "scrap_rubber_weight",
          "scrap_rubber_drc_grade",
          "total_amount",
        ]);
      }
      setCurrent(current + 1);
    } catch (e) {
      console.log("Validation failed:", e);
    }
  };

  const prev = () => {
    setCurrent(current - 1);
  };

  const onFinish = async () => {
    try {
      const values = await form.validateFields();

      const purchaseDateStr = values.purchase_date?.format?.("YYYY-MM-DD");
      const transportDateStr =
        values.transport?.transport_date?.format?.("YYYY-MM-DD");

      const payload: IExternalMaterialData = {
        ...values,
        purchase_date: purchaseDateStr,
        transport: {
          ...values.transport,
          transport_date: transportDateStr,
          pickup_time:
            transportDateStr && values.transport?.pickup_time
              ? `${transportDateStr} ${values.transport.pickup_time.format("HH:mm:ss")}`
              : values.transport?.pickup_time?.format?.("YYYY-MM-DD HH:mm:ss"),
          delivery_time:
            transportDateStr && values.transport?.delivery_time
              ? `${transportDateStr} ${values.transport.delivery_time.format("HH:mm:ss")}`
              : values.transport?.delivery_time?.format?.(
                  "YYYY-MM-DD HH:mm:ss",
                ),
        },
      };

      if (record?.external_material_code) {
        mutateUpdate.mutate({
          external_material_code: record.external_material_code,
          data: payload,
        });
      } else {
        mutateCreate.mutate(payload);
      }
    } catch (e) {
      if (e && typeof e === "object" && "errorFields" in e) {
        message.warning(t("check_steps_warning"));
        return;
      }
      handleApiError(e);
    }
  };

  return (
    <BaseSheet
      title={
        record ? t("update_title") : t("add_title")
      }
      width={1200}
      onClose={onClose}
      open={open}
      extra={
        <Space>
          <Button onClick={onClose}>{tc("cancel")}</Button>
          {current > 0 && <Button onClick={prev}>{tc("back")}</Button>}
          {current < 2 ? (
            <Button type="primary" onClick={next}>
              {tc("continue")}
            </Button>
          ) : (
            <Button
              type="primary"
              onClick={onFinish}
              loading={mutateCreate.isPending || mutateUpdate.isPending}>
              {tc("save")}
            </Button>
          )}
        </Space>
      }>
      <Steps
        current={current}
        items={[
          { title: t("step_plots") },
          { title: t("step_general") },
          { title: t("step_transport") },
        ]}
        style={{ marginBottom: 24 }}
      />

      <Form form={form} layout="vertical" preserve={true}>
        {/* Step 1: Thông tin vườn */}
        <div style={{ display: current === 0 ? "block" : "none" }}>
          <StepPlots />
        </div>

        {/* Step 2: Thông tin chung */}
        <div style={{ display: current === 1 ? "block" : "none" }}>
          <StepGeneralInfo />
        </div>

        {/* Step 3: Vận chuyển */}
        <div style={{ display: current === 2 ? "block" : "none" }}>
          <StepTransport />
        </div>
      </Form>
    </BaseSheet>
  );
};

export default ExternalMaterialForm;
