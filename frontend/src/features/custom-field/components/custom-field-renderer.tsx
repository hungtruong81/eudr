"use client";
import {
  DatePicker,
  Form,
  Input,
  InputNumber,
  Select,
  Skeleton,
  Switch,
} from "antd";
import { useQuery } from "@tanstack/react-query";
import React, { forwardRef, useEffect, useImperativeHandle } from "react";
import {
  getSchemaCustomField,
  getEntityValue,
  setEntityValue,
} from "../action";
import { ICustomField, IEntityValue } from "../types";
import dayjs from "dayjs";

export interface CustomFieldRendererRef {
  /** Call after parent entity is created/updated to persist custom field values */
  saveValues: (entityId: string | number) => Promise<void>;
}

interface CustomFieldRendererProps {
  /** Entity type key, e.g. "product_lot_import_none_eudr" */
  entityType: string;
  /**
   * Existing entity ID.
   * When provided: pre-fills form inputs via getEntityValue.
   * When omitted: create mode — no pre-fill.
   */
  entityId?: string | number | null;
  /** Form.Item name prefix (default: "custom_fields") */
  namePrefix?: string;
  /** Controls whether queries run (tie to modal open state) */
  enabled?: boolean;
}

/**
 * Renders active custom fields for a given entity_type as Ant Design Form.Items.
 * Must be placed inside an Ant Design <Form> component.
 *
 * Usage:
 *   const ref = useRef<CustomFieldRendererRef>(null);
 *   // After entity is saved:
 *   await ref.current?.saveValues(entity.id);
 */
const CustomFieldRenderer = forwardRef<
  CustomFieldRendererRef,
  CustomFieldRendererProps
>(
  (
    { entityType, entityId, namePrefix = "custom_fields", enabled = true },
    ref,
  ) => {
    const form = Form.useFormInstance();

    // 1. Schema (field definitions with field_id, field_key, field_type…)
    const { data: schemaData, isLoading } = useQuery({
      queryKey: ["custom-field-schema", entityType],
      queryFn: () => getSchemaCustomField(entityType),
      enabled: !!entityType && enabled,
      staleTime: 5 * 60 * 1000,
      refetchOnWindowFocus: false,
    });

    const fields: ICustomField[] = (schemaData?.schema || []).sort(
      (a, b) => a.sort_order - b.sort_order,
    );

    // 2. Existing values — only in edit mode (entityId provided)
    const { data: valuesData } = useQuery({
      queryKey: ["custom-field-values", entityType, entityId],
      queryFn: () => getEntityValue(entityType, String(entityId!)),
      enabled: !!entityId && !!entityType && enabled,
      staleTime: 5 * 60 * 1000,
      refetchOnWindowFocus: false,
    });

    // 3. Pre-fill form when existing values load
    useEffect(() => {
      const values = valuesData?.data as IEntityValue[] | undefined;
      if (!values?.length) return;

      const prefill: Record<string, any> = {};
      values.forEach((v) => {
        prefill[v.field_key] = v.value;
      });
      form.setFieldsValue({ [namePrefix]: prefill });
    }, [valuesData, form, namePrefix]);

    // 4. Expose saveValues(entityId) — call from parent after entity is created/updated
    useImperativeHandle(ref, () => ({
      saveValues: async (id: string | number) => {
        const formValues =
          (form.getFieldValue(namePrefix) as Record<string, any>) || {};

        const payload = fields
          .filter(
            (f) =>
              formValues[f.field_key] !== undefined &&
              formValues[f.field_key] !== null &&
              formValues[f.field_key] !== "",
          )
          .map((f) => {
            let value = formValues[f.field_key];
            // Format Dayjs date objects → YYYY-MM-DD string for backend
            if (f.field_type === "date" && value && dayjs.isDayjs(value)) {
              value = value.format("YYYY-MM-DD");
            }
            return { field_id: f.field_id, value };
          });

        if (payload.length > 0) {
          await setEntityValue(entityType, String(id), { values: payload });
        }
      },
    }));

    if (isLoading) return <Skeleton active paragraph={{ rows: 2 }} />;
    if (fields.length === 0) return null;

    return (
      <>
        {fields.map((field) => (
          <Form.Item
            key={field.field_code}
            name={[namePrefix, field.field_key]}
            label={field.field_label}
            tooltip={field.field_description}
            rules={
              field.is_required
                ? [
                    {
                      required: true,
                      message: `${field.field_label} là bắt buộc`,
                    },
                  ]
                : undefined
            }>
            {field.field_type === "text" && (
              <Input placeholder={field.field_label} />
            )}
            {field.field_type === "number" && (
              <InputNumber
                style={{ width: "100%" }}
                placeholder={field.field_label}
              />
            )}
            {field.field_type === "date" && (
              <DatePicker style={{ width: "100%" }} format="DD/MM/YYYY" />
            )}
            {field.field_type === "boolean" && <Switch />}
            {field.field_type === "select" && (
              <Select
                placeholder={field.field_label}
                options={(field.options || []).map((o) => ({
                  label: o,
                  value: o,
                }))}
              />
            )}
            {!["text", "number", "date", "boolean", "select"].includes(
              field.field_type,
            ) && <Input placeholder={field.field_label} />}
          </Form.Item>
        ))}
      </>
    );
  },
);

CustomFieldRenderer.displayName = "CustomFieldRenderer";
export default CustomFieldRenderer;
