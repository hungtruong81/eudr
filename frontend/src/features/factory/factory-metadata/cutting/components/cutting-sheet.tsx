"use client";
import React, { useEffect } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Form, Input, message, Row, Col } from "antd";
import {
  createCuttingMachine,
  updateCuttingMachine,
  generateCode,
} from "../actions";
import { ICutting } from "../types";
import { getFactory } from "../../factory/actions";
import { IFactory } from "../../factory/types";
import BaseSheet from "@/components/shared/base-sheet";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";

interface CuttingSheetProps {
  open: boolean;
  onClose: () => void;
  record: ICutting | null;
}

export const CuttingSheet = ({ open, onClose, record }: CuttingSheetProps) => {
  const t = useTranslations("Factory.metadata.cutting");
  const tm = useTranslations("Factory.metadata");

  const [form] = Form.useForm();
  const queryClient = useQueryClient();

  const createMutation = useMutation({
    mutationFn: createCuttingMachine,
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["cutting-machines"] });
      onClose();
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({
      cutting_machine_code,
      data,
    }: {
      cutting_machine_code: string;
      data: any;
    }) => updateCuttingMachine(cutting_machine_code, data),
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["cutting-machines"] });
      onClose();
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const fetchGeneratedCode = async () => {
    try {
      const res = await generateCode();
      if (res?.data?.cutting_machine_code) {
        form.setFieldValue(
          "cutting_machine_code",
          res.data.cutting_machine_code,
        );
      }
    } catch (error) {
      console.error("Error fetching cutting machine code:", error);
    }
  };

  useEffect(() => {
    if (open) {
      if (record) {
        form.setFieldsValue(record);
      } else {
        form.resetFields();
        fetchGeneratedCode();
      }
    }
  }, [open, record, form]);

  const onFinish = (values: any) => {
    if (record) {
      updateMutation.mutate({
        cutting_machine_code: record.cutting_machine_code,
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
      onOk={() => form.submit()}
      title={record ? t("edit_title") : t("add_title")}
      loading={createMutation.isPending || updateMutation.isPending}
      width={500}>
      <Form form={form} layout="vertical" onFinish={onFinish}>
        <Row gutter={16}>
          <Col span={24}>
            <Form.Item
              name="cutting_machine_name"
              label={t("name")}
              rules={[{ required: true, message: t("name_required") }]}>
              <Input placeholder={t("enter_name")} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="cutting_machine_code"
              label={t("code")}
              rules={[{ required: true, message: t("code_required") }]}>
              <Input
                placeholder={t("enter_code")}
                disabled
                className="uppercase"
              />
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
        </Row>
      </Form>
    </BaseSheet>
  );
};
