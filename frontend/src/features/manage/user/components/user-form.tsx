"use client";

import BaseSheet from "@/components/shared/base-sheet";
import { REGISTER_TYPE, REGISTER_TYPE_LABEL } from "@/constants/register-types";
import { useUser } from "@/providers/user-context";
import { Form, Input, Select } from "antd";
import React, { useEffect } from "react";
import { useTranslations } from "next-intl";
import { IUserCompany } from "../types";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getCompanys } from "../../company/actions";
import { ICompany } from "../../company/types";

interface UserFormProps {
  open: boolean;
  onClose: () => void;
  onFinish: (values: any) => Promise<void>;
  loading: boolean;
  record: IUserCompany | null;
}

const UserForm = ({
  open,
  onClose,
  onFinish,
  loading,
  record,
}: UserFormProps) => {
  const [form] = Form.useForm();
  const { isAdmin } = useUser();
  const t = useTranslations("Manage.User");
  const tc = useTranslations("Common");
  const tr = useTranslations("RegisterType");

  useEffect(() => {
    if (open) {
      if (record) {
        form.setFieldsValue(record);
      } else {
        form.resetFields();
      }
    }
  }, [open, record, form]);

  const handleSubmit = async () => {
    const values = await form.validateFields();
    await onFinish(values);
  };

  return (
    <BaseSheet
      title={record ? t("edit_title") : t("create_title")}
      open={open}
      onClose={onClose}
      onOk={handleSubmit}
      loading={loading}
      okText={record ? tc("update") : tc("create")}>
      <Form form={form} layout="vertical">
        <Form.Item
          name="full_name"
          label={t("full_name")}
          rules={[{ required: true, message: t("name_required") }]}>
          <Input placeholder={t("name_required")} />
        </Form.Item>
        <Form.Item
          name="email"
          label={t("email")}
          rules={[
            { required: true, message: t("email_required") },
            { type: "email", message: t("email_invalid") },
          ]}>
          <Input placeholder={t("email")} disabled={!!record} />
        </Form.Item>
        <Form.Item
          name="phone"
          label={t("phone")}
          rules={[{ required: true, message: t("phone_required") }]}>
          <Input placeholder={t("phone")} disabled={!!record} />
        </Form.Item>
        <Form.Item
          name="password"
          label={t("password")}
          rules={[{ required: !record, message: t("password_required") }]}>
          <Input.Password
            placeholder={
              record ? t("password_hint") : t("password_placeholder")
            }
          />
        </Form.Item>
        {!record && (
          <Form.Item
            name="register_type"
            label={t("register_type")}
            rules={[
              { required: true, message: t("register_type_required") },
            ]}>
            <Select
              placeholder={t("register_type_placeholder")}
              options={Object.values(REGISTER_TYPE).map((type) => ({
                label: tr(type),
                value: type,
              }))}
            />
          </Form.Item>
        )}
        {isAdmin && !record && (
          <Form.Item
            name="company_id"
            label={tc("company")}
            rules={[{ required: true, message: t("company_required") }]}>
            <InfiniteScrollSelect<ICompany>
              placeholder={tc("select_company")}
              queryKey={["company-list-form"]}
              fetchFn={getCompanys}
              mapOption={(item) => ({
                label: item.company_name,
                value: String(item.company_id),
              })}
            />
          </Form.Item>
        )}
      </Form>
    </BaseSheet>
  );
};

export default UserForm;
