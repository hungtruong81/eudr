"use client";
import { useQuery } from "@tanstack/react-query";
import { Space, Typography, Card } from "antd";
import React, { useState, useMemo } from "react";
import { getCustomFields } from "../action";
import { CUSTOM_FIELD_ENTITIES, ICustomFieldEntitySummary } from "../types";
import CustomFieldTable from "./custom-field-table";
import { usePermissions } from "@/contexts/permission-context";
import { useTranslations } from "next-intl";
import CustomFieldFormBuilder from "./custom-field-form-builder";

const { Title } = Typography;

const CustomFieldManager = () => {
  const t = useTranslations("Manage.CustomField");
  const { custom_field } = usePermissions();
  const [open, setOpen] = useState(false);
  const [selectedEntity, setSelectedEntity] = useState<string | null>(null);

  // Fetch all custom fields to count them by entity
  const { data, isLoading } = useQuery({
    queryKey: ["custom-fields-all"],
    queryFn: () =>
      getCustomFields({
        page: 1,
        limit: 100,
        entity_type: "",
        field_type: "",
        status: "all",
      }),
  });

  const entitySummaries: ICustomFieldEntitySummary[] = useMemo(() => {
    const allFields = data?.data?.records || [];
    return CUSTOM_FIELD_ENTITIES.map((entity) => {
      const count = allFields.filter((f) => f.entity_type === entity).length;
      return {
        entity_type: entity,
        field_count: count,
      };
    });
  }, [data]);

  const handleEdit = (entityType: string) => {
    setSelectedEntity(entityType);
    setOpen(true);
  };

  const handleClose = () => {
    setOpen(false);
    setSelectedEntity(null);
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <Card>
        <CustomFieldTable
          data={entitySummaries}
          loading={isLoading}
          onEdit={handleEdit}
          permissions={custom_field || {}}
        />
      </Card>

      {selectedEntity && (
        <CustomFieldFormBuilder
          open={open}
          onClose={handleClose}
          entityType={selectedEntity}
        />
      )}
    </Space>
  );
};

export default CustomFieldManager;
