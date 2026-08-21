type BaseField = {
  field_key: string;
  field_label: string;
  field_description?: string;
  entity_type: string;
  is_required: boolean;
  is_searchable: boolean;
  sort_order: number;
  status: "active" | "inactive" | string;
};

export interface ICustomField {
  field_id: number;
  field_code: string;
  field_key: string;
  field_label: string;
  field_description?: string;
  entity_type: string;
  field_type: string;
  options?: string[];
  is_required: boolean;
  is_searchable: boolean;
  sort_order: number;
  status: "active" | "inactive" | string;
  company_id: number;
  created_by: number;
  created_at: string;
  updated_by: number;
  updated_at: string | null;
  deleted_by: number;
  deleted_at: string | null;
}

export type ICustomFielData =
  | (BaseField & {
      field_type: "select";
      options: string[];
    })
  | (BaseField & {
      field_type: Exclude<string, "select">;
      options?: never;
    });

export const CUSTOM_FIELD_ENTITIES = [
  "land",
  "plant",
  // "harvest",
  // "customer",
  // "product",
  // "sales_order",
  "product_lot_import_none_eudr",
] as const;

export type CustomFieldEntityType = (typeof CUSTOM_FIELD_ENTITIES)[number];

export interface ICustomFieldEntitySummary {
  entity_type: string;
  field_count: number;
}

/** Single custom field value item sent to setEntityValue */
export interface ISetEntityValue {
  field_id: number;
  value: any;
}

/** Single custom field value item returned by getEntityValue */
export interface IEntityValue {
  field_id: number;
  field_key: string;
  value: any;
}
