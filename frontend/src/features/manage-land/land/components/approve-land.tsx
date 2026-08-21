import { Modal, Form, Select, message } from "antd";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { approveLand } from "@/features/manage-land/land/actions";
import { IPlot } from "@/features/manage-land/land/types";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";
import AppModal from "@/components/modal";

interface Props {
  open: boolean;
  onCancel: () => void;
  record: IPlot | null;
}

const ApproveLandModal = ({ open, onCancel, record }: Props) => {
  const t = useTranslations("ManageLand.Land");
  const tCommon = useTranslations("Common");
  const [form] = Form.useForm();
  const queryClient = useQueryClient();

  const mutation = useMutation({
    mutationFn: (values: { eudr_status: number; is_approved: number }) =>
      approveLand(record!.plot_code, values.eudr_status, values.is_approved),
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["land"] });
      onCancel();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const handleOk = () => {
    form.validateFields().then((values) => {
      mutation.mutate(values);
    });
  };

  return (
    <AppModal
      title={`${t("approve_land")}: ${record?.plot_code?.toUpperCase()}`}
      open={open}
      onOk={handleOk}
      onCancel={onCancel}
      confirmLoading={mutation.isPending}
      okText={tCommon("confirm")}
      cancelText={tCommon("cancel")}>
      <Form
        form={form}
        layout="vertical"
        initialValues={{ is_approved: 1, eudr_status: 1 }}>
        <Form.Item
          name="is_approved"
          label={t("approval_status")}
          rules={[{ required: true }]}>
          <Select
            options={[
              { value: 1, label: t("approve") },
              { value: 0, label: t("reject") },
            ]}
          />
        </Form.Item>
        <Form.Item
          name="eudr_status"
          label={t("eudr_status")}
          rules={[{ required: true }]}>
          <Select
            options={[
              { value: 0, label: t("not_checked") },
              { value: 1, label: t("valid") },
              { value: 2, label: t("invalid") },
            ]}
          />
        </Form.Item>
      </Form>
    </AppModal>
  );
};

export default ApproveLandModal;
