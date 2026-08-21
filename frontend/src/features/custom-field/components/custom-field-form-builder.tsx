import {
  MinusCircleOutlined,
  PlusOutlined,
  ArrowUpOutlined,
  ArrowDownOutlined,
} from "@ant-design/icons";
import {
  Button,
  Col,
  Form,
  Input,
  Row,
  Select,
  Space,
  Switch,
  Card,
  message,
  Typography,
  DatePicker,
  InputNumber,
  Divider,
} from "antd";
import React, { useEffect, useRef, useState } from "react";
import { useTranslations } from "next-intl";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { ICustomField } from "../types";
import BaseSheet from "@/components/shared/base-sheet";
import {
  getCustomFields,
  createCustomField,
  updateCustomField,
  deleteCustomField,
} from "../action";
import { handleApiError } from "@/lib/api-error";

const { Text } = Typography;

interface CustomFieldFormBuilderProps {
  open: boolean;
  onClose: () => void;
  entityType: string;
}

const CustomFieldFormBuilder = ({
  open,
  onClose,
  entityType,
}: CustomFieldFormBuilderProps) => {
  const [form] = Form.useForm();
  const t = useTranslations("Manage.CustomField");
  const tc = useTranslations("Common");
  const queryClient = useQueryClient();
  const [isSubmitting, setIsSubmitting] = useState(false);

  const watchedFields = Form.useWatch("fields", form) || [];

  const { data, isLoading, refetch } = useQuery({
    queryKey: ["custom-fields", entityType],
    queryFn: () =>
      getCustomFields({
        page: 1,
        limit: 100,
        entity_type: entityType,
        status: "all",
        field_type: "",
      }),
    enabled: !!entityType && open,
    staleTime: 30_000, // prevent background refetch from resetting user edits
    refetchOnWindowFocus: false,
  });

  const originalFields = data?.data?.records || [];

  // Guard: only populate form once per modal open session
  const isInitializedRef = useRef(false);

  // Reset guard when modal closes
  useEffect(() => {
    if (!open) {
      form.resetFields();
      isInitializedRef.current = false;
    }
  }, [open, form]);

  // Populate form once, when data has loaded (not on subsequent re-renders)
  useEffect(() => {
    if (open && !isLoading && !isInitializedRef.current) {
      const sortedFields = [...originalFields].sort(
        (a, b) => a.sort_order - b.sort_order,
      );
      form.setFieldsValue({ fields: sortedFields });
      isInitializedRef.current = true;
    }
    // originalFields intentionally excluded — the ref guard prevents re-init
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open, isLoading]);

  const onFinish = async (values: any) => {
    try {
      setIsSubmitting(true);
      const currentFields = values.fields || [];

      // Calculate missing fields (deleted)
      const currentFieldCodes = currentFields
        .filter((f: any) => f.field_code)
        .map((f: any) => f.field_code);

      const deletedFields = originalFields.filter(
        (f) => !currentFieldCodes.includes(f.field_code),
      );

      // Create promises
      const promises: Promise<any>[] = [];

      // 1. Delete fields
      deletedFields.forEach((f) => {
        promises.push(deleteCustomField(f.field_code));
      });

      // 2. Create or Update fields
      currentFields.forEach((field: any, index: number) => {
        const payload = {
          ...field,
          sort_order: index + 1,
          entity_type: entityType,
        };

        // Remove options if not select
        if (payload.field_type !== "select") {
          delete payload.options;
        }

        if (field.field_code) {
          promises.push(updateCustomField(field.field_code, payload));
        } else {
          promises.push(createCustomField(payload));
        }
      });

      await Promise.all(promises);
      message.success(tc("save_success"));
      queryClient.invalidateQueries({ queryKey: ["custom-fields-all"] });
      refetch();
    } catch (error) {
      handleApiError(error);
    } finally {
      setIsSubmitting(false);
    }
  };

  const moveField = (
    index: number,
    direction: "up" | "down",
    moveFn: Function,
  ) => {
    if (direction === "up" && index > 0) {
      moveFn(index, index - 1);
    } else if (direction === "down") {
      const fields = form.getFieldValue("fields") || [];
      if (index < fields.length - 1) {
        moveFn(index, index + 1);
      }
    }
  };

  return (
    <BaseSheet
      title={t("edit_form") + ` - ${t(`entity_${entityType}` as any)}`}
      open={open}
      onClose={onClose}
      onOk={() => form.submit()}
      okText={t("save_form")}
      cancelText={tc("cancel")}
      width={1400}
      loading={isLoading || isSubmitting}>
      <Row gutter={24}>
        <Col span={14}>
          <Form form={form} layout="vertical" onFinish={onFinish}>
            <div style={{ marginBottom: 16 }}>
              <Text type="secondary">{t("field_list")}</Text>
            </div>

            <Form.List name="fields">
              {(fields, { add, remove, move }) => (
                <>
                  {fields.map(({ key, name, ...restField }, index) => {
                    return (
                      <Card
                        key={key}
                        size="small"
                        style={{ marginBottom: 16 }}
                        title={`Field #${index + 1}`}
                        extra={
                          <Space>
                            <Button
                              type="text"
                              icon={<ArrowUpOutlined />}
                              disabled={index === 0}
                              onClick={() => moveField(index, "up", move)}
                            />
                            <Button
                              type="text"
                              icon={<ArrowDownOutlined />}
                              disabled={index === fields.length - 1}
                              onClick={() => moveField(index, "down", move)}
                            />
                            <Button
                              type="text"
                              danger
                              icon={<MinusCircleOutlined />}
                              onClick={() => remove(name)}
                            />
                          </Space>
                        }>
                        <Form.Item
                          {...restField}
                          name={[name, "field_code"]}
                          hidden>
                          <Input />
                        </Form.Item>

                        <Row gutter={16}>
                          <Col span={8}>
                            <Form.Item
                              {...restField}
                              name={[name, "field_key"]}
                              label={t("field_key")}
                              rules={[
                                { required: true, message: t("enter_key") },
                              ]}>
                              <Input placeholder={t("enter_key")} />
                            </Form.Item>
                          </Col>
                          <Col span={8}>
                            <Form.Item
                              {...restField}
                              name={[name, "field_label"]}
                              label={t("field_label")}
                              rules={[
                                { required: true, message: t("enter_label") },
                              ]}>
                              <Input placeholder={t("enter_label")} />
                            </Form.Item>
                          </Col>
                          <Col span={8}>
                            <Form.Item
                              {...restField}
                              name={[name, "field_type"]}
                              label={t("field_type")}
                              rules={[
                                { required: true, message: t("select_type") },
                              ]}>
                              <Select
                                placeholder={t("select_type")}
                                options={[
                                  { label: "Text", value: "text" },
                                  { label: "Number", value: "number" },
                                  { label: "Date", value: "date" },
                                  { label: "Select", value: "select" },
                                  { label: "Boolean", value: "boolean" },
                                ]}
                              />
                            </Form.Item>
                          </Col>
                        </Row>

                        <Form.Item
                          {...restField}
                          name={[name, "field_description"]}
                          label={t("field_description")}>
                          <Input.TextArea
                            rows={1}
                            placeholder={t("field_description")}
                          />
                        </Form.Item>

                        {/* Conditional render for Select options */}
                        <Form.Item
                          noStyle
                          shouldUpdate={(prevValues, currentValues) =>
                            prevValues.fields?.[name]?.field_type !==
                            currentValues.fields?.[name]?.field_type
                          }>
                          {({ getFieldValue }) =>
                            getFieldValue(["fields", name, "field_type"]) ===
                            "select" ? (
                              <Form.List name={[name, "options"]}>
                                {(
                                  optionFields,
                                  { add: addOption, remove: removeOption },
                                ) => (
                                  <div style={{ marginBottom: 16 }}>
                                    <div
                                      style={{
                                        marginBottom: 8,
                                        fontWeight: 500,
                                      }}>
                                      {t("options")}
                                    </div>
                                    {optionFields.map((optField) => (
                                      <Space
                                        key={optField.key}
                                        style={{
                                          display: "flex",
                                          marginBottom: 8,
                                        }}
                                        align="baseline">
                                        <Form.Item
                                          {...optField}
                                          rules={[
                                            {
                                              required: true,
                                              message: "Missing option",
                                            },
                                          ]}>
                                          <Input placeholder="Option value" />
                                        </Form.Item>
                                        <MinusCircleOutlined
                                          onClick={() =>
                                            removeOption(optField.name)
                                          }
                                          style={{ color: "red" }}
                                        />
                                      </Space>
                                    ))}
                                    <Button
                                      type="dashed"
                                      onClick={() => addOption()}
                                      icon={<PlusOutlined />}
                                      size="small">
                                      {t("add_option")}
                                    </Button>
                                  </div>
                                )}
                              </Form.List>
                            ) : null
                          }
                        </Form.Item>

                        <Row gutter={16}>
                          <Col span={8}>
                            <Form.Item
                              {...restField}
                              name={[name, "status"]}
                              label={tc("status")}>
                              <Select
                                options={[
                                  {
                                    label: tc("status_active"),
                                    value: "active",
                                  },
                                  {
                                    label: tc("status_inactive"),
                                    value: "inactive",
                                  },
                                ]}
                              />
                            </Form.Item>
                          </Col>
                          <Col span={8}>
                            <Form.Item
                              {...restField}
                              name={[name, "is_required"]}
                              label={t("is_required")}
                              valuePropName="checked">
                              <Switch />
                            </Form.Item>
                          </Col>
                          <Col span={8}>
                            <Form.Item
                              {...restField}
                              name={[name, "is_searchable"]}
                              label={t("is_searchable")}
                              valuePropName="checked">
                              <Switch />
                            </Form.Item>
                          </Col>
                        </Row>
                      </Card>
                    );
                  })}

                  <Button
                    type="dashed"
                    onClick={() =>
                      add({
                        status: "active",
                        is_required: false,
                        is_searchable: false,
                        field_type: "text",
                      })
                    }
                    htmlType="button"
                    block
                    icon={<PlusOutlined />}
                    size="large"
                    style={{ marginTop: 16 }}>
                    {t("add_field")}
                  </Button>
                </>
              )}
            </Form.List>
          </Form>
        </Col>

        <Col span={10}>
          <div style={{ marginBottom: 16 }}>
            <Text type="secondary">
              {(t as any).has("form_preview")
                ? (t as any)("form_preview")
                : "Form Preview"}
            </Text>
          </div>
          <Card
            className="bg-slate-50"
            style={{ minHeight: "calc(100vh - 200px)" }}>
            <Form layout="vertical">
              {watchedFields
                .filter(
                  (f: any) => f && f.field_label && f.status !== "inactive",
                )
                .map((f: any, i: number) => (
                  <Form.Item
                    key={i}
                    label={f.field_label}
                    required={f.is_required}
                    tooltip={f.field_description}>
                    {f.field_type === "text" && (
                      <Input placeholder={f.field_label} />
                    )}
                    {f.field_type === "number" && (
                      <InputNumber
                        style={{ width: "100%" }}
                        placeholder={f.field_label}
                      />
                    )}
                    {f.field_type === "date" && (
                      <DatePicker
                        style={{ width: "100%" }}
                        placeholder={f.field_label}
                      />
                    )}
                    {f.field_type === "boolean" && <Switch />}
                    {f.field_type === "select" && (
                      <Select
                        placeholder={f.field_label}
                        options={(f.options || [])
                          .filter((o: any) => o)
                          .map((o: any) => ({ label: o, value: o }))}
                      />
                    )}
                  </Form.Item>
                ))}
              {(!watchedFields ||
                watchedFields.filter(
                  (f: any) => f && f.field_label && f.status !== "inactive",
                ).length === 0) && (
                <div
                  style={{
                    textAlign: "center",
                    padding: "40px 0",
                    color: "#999",
                  }}>
                  {tc("no_data")}
                </div>
              )}
            </Form>
          </Card>
        </Col>
      </Row>
    </BaseSheet>
  );
};

export default CustomFieldFormBuilder;
