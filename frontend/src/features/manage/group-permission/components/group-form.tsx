import BaseSheet from "@/components/shared/base-sheet";
import { Col, Form, Input, Row } from "antd";
import React, { useEffect } from "react";
import { ICompanyGroup, ICompanyGroupData } from "../types";
import { useTranslations } from "next-intl";

interface GroupFormProps {
  open: boolean;
  onClose: () => void;
  record: ICompanyGroup | null;
  onFinish: (values: ICompanyGroupData) => Promise<void>;
  loading?: boolean;
}

const GroupForm = ({
  open,
  onClose,
  record,
  onFinish,
  loading,
}: GroupFormProps) => {
  const [form] = Form.useForm();
  const t = useTranslations("Manage.GroupPermission");

  useEffect(() => {
    if (open) {
      if (record) {
        form.setFieldsValue(record);
      } else {
        form.resetFields();
      }
    }
  }, [open, record, form]);

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={() => form.submit()}
      title={record ? t("edit_title") : t("create_title")}
      loading={loading}
      width={500}>
      <Form form={form} layout="vertical" onFinish={onFinish}>
        <Row gutter={16}>
          <Col span={24}>
            <Form.Item
              name="name"
              label={t("group_name")}
              rules={[{ required: true, message: t("name_required") }]}>
              <Input placeholder={t("placeholder_name")} />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item name="description" label={t("description")}>
              <Input.TextArea rows={3} placeholder={t("placeholder_description")} />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </BaseSheet>
  );
};

export default GroupForm;
