"use client";
import React, { useEffect } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import {
  Form,
  Input,
  message,
  Row,
  Col,
  InputNumber,
  Select,
} from "antd";
import {
  createRawMaterialTank,
  updateRawMaterialTank,
} from "../actions";
import { IRawMaterialTank } from "../types";
import { getFactory } from "../../factory/actions";
import { IFactory } from "../../factory/types";
import BaseSheet from "@/components/shared/base-sheet";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";

interface RawMaterialTankSheetProps {
  open: boolean;
  onClose: () => void;
  record: IRawMaterialTank | null;
}

export const RawMaterialTankSheet = ({
  open,
  onClose,
  record,
}: RawMaterialTankSheetProps) => {
  const t = useTranslations("Factory.metadata.raw_material_tank");
  const tm = useTranslations("Factory.metadata");
  const tc = useTranslations("Common");
  
  const [form] = Form.useForm();
  const queryClient = useQueryClient();

  const createMutation = useMutation({
    mutationFn: createRawMaterialTank,
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["raw-material-tank"] });
      onClose();
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({
      raw_material_tank_code,
      data,
    }: {
      raw_material_tank_code: string;
      data: any;
    }) => updateRawMaterialTank(raw_material_tank_code, data),
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["raw-material-tank"] });
      onClose();
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  useEffect(() => {
    if (open && record) {
      form.setFieldsValue(record);
    } else if (open) {
      form.resetFields();
    }
  }, [open, record, form]);

  const onFinish = (values: any) => {
    if (record) {
      updateMutation.mutate({
        raw_material_tank_code: record.raw_material_tank_code,
        data: values,
      });
    } else {
      createMutation.mutate(values);
    }
  };

  const mapFactoryOptions = (item: IFactory) => ({
    value: item.factory_id.toString(),
    label: item.factory_name,
  });

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={form.submit}
      title={record ? t("edit_title") : t("add_title")}
      loading={createMutation.isPending || updateMutation.isPending}
      width={600}>
      <Form form={form} layout="vertical" onFinish={onFinish}>
        <Row gutter={16}>
          <Col span={24}>
            <Form.Item
              name="raw_material_tank_name"
              label={t("name")}
              rules={[
                { required: true, message: t("enter_name") },
              ]}>
              <Input placeholder={t("enter_name")} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="factory_id"
              label={t("factory")}
              rules={[{ required: true, message: tm("factory_required") }]}>
              <InfiniteScrollSelect<IFactory>
                queryKey={["factories-select"]}
                fetchFn={(p) => getFactory({ page: p.page, limit: p.limit })}
                mapOption={mapFactoryOptions}
                placeholder={tm("select_factory")}
                allowClear
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="tank_type"
              label={t("tank_type")}
              rules={[
                { required: true, message: t("select_tank_type") },
              ]}>
              <Select
                placeholder={t("select_tank_type")}
                options={[
                  { value: "latex", label: t("latex") },
                  { value: "scrap_rubber", label: t("scrap_rubber") },
                  { value: "mixed", label: t("mixed") },
                ]}
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="capacity"
              label={t("capacity")}
              rules={[{ required: true, message: t("enter_capacity") }]}>
              <InputNumber
                style={{ width: "100%" }}
                min={0}
                placeholder="VD: 5000"
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="location"
              label={t("location")}
              rules={[{ required: true, message: t("enter_location") }]}>
              <Input placeholder="VD: Khu A" />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item name="notes" label={tc("notes")}>
              <Input.TextArea rows={3} placeholder={tc("no_notes")} />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </BaseSheet>
  );
};
