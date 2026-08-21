import BaseSheet from "@/components/shared/base-sheet";
import { Col, Form, Input, Row } from "antd";
import React, { useEffect } from "react";
import { ICompany, ICompanyData } from "../types";
import { useTranslations } from "next-intl";

interface CompanyFormProps {
  open: boolean;
  onClose: () => void;
  record: ICompany | null;
  onFinish: (values: ICompanyData) => Promise<void>;
  loading?: boolean;
}

const CompanyForm = ({
  open,
  onClose,
  record,
  onFinish,
  loading,
}: CompanyFormProps) => {
  const [form] = Form.useForm();
  const t = useTranslations("Manage.Company");

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
      width={600}>
      <Form form={form} layout="vertical" onFinish={onFinish}>
        <Row gutter={16}>
          <Col span={24}>
            <Form.Item
              name="company_name"
              label={t("company_name")}
              rules={[{ required: true, message: t("name_required") }]}>
              <Input placeholder={t("placeholder_name")} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="short_name"
              label={t("short_name")}
              rules={[
                { required: true, message: t("short_name_required") },
              ]}>
              <Input placeholder={t("placeholder_short_name")} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="tax_code"
              label={t("tax_code")}
              rules={[{ required: true, message: t("tax_code_required") }]}>
              <Input placeholder={t("placeholder_tax_code")} />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item name="address" label={t("address")}>
              <Input.TextArea rows={2} placeholder={t("placeholder_address")} />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item name="website" label={t("website")}>
              <Input placeholder={t("placeholder_website")} />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </BaseSheet>
  );
};

export default CompanyForm;
