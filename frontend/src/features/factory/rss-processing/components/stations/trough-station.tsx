import {
  Button,
  Card,
  Empty,
  Flex,
  Form,
  InputNumber,
  Space,
  Tag,
  Typography,
  message,
} from "antd";
import { useEffect, useMemo, useState } from "react";
import { QrScannerInput } from "../shared/qr-scanner-input";
import {
  BarcodeOutlined,
  DeploymentUnitOutlined,
  ScissorOutlined,
} from "@ant-design/icons";
import {
  CutBatch,
  RSS_CUT_EYE_OPTIONS,
  RolledBatch,
  RssQuality,
  RssWorkflowPlan,
  TroughBatch,
  createWorkflowCode,
  getRssQualityLabel,
} from "../shared/rss-process-state";
import {
  RssQualityFlowPicker,
  RssQualityPicker,
  RssSelectedSummary,
  RssTechnicalDetails,
  RssTilePicker,
  formatQualityFlowLabel,
} from "../shared/rss-selection";

const { Text } = Typography;

type TroughStationMode = "cut" | "roll";

interface TroughStationProps {
  mode: TroughStationMode;
  workflowPlan?: RssWorkflowPlan | null;
  troughBatches: TroughBatch[];
  cutBatches: CutBatch[];
  rolledBatches: RolledBatch[];
  onCreateCutBatch: (batch: CutBatch) => void;
  onCreateRolledBatch: (batch: RolledBatch) => void;
}

interface CutFormValues {
  sourceTroughBatchId: string;
  sheetCount: number;
  estimatedSheetCount: number;
  quality: RssQuality;
}

const getFallbackTroughBatch = (
  troughCode: string,
  workflowPlan?: RssWorkflowPlan | null,
): TroughBatch => ({
  id: createWorkflowCode(`TRB-${troughCode}`),
  tankCode: workflowPlan?.tankCode ?? "NA",
  troughCode,
  volumeLiters: workflowPlan?.defaultVolumeLiters ?? 0,
  quality: workflowPlan?.quality ?? "NA",
  createdAt: "Vừa scan",
});

const getFallbackCutBatch = (
  troughCode: string,
  workflowPlan?: RssWorkflowPlan | null,
): CutBatch => ({
  id: createWorkflowCode(`CUT-${troughCode}`),
  sourceTroughBatchId: `TRB-${troughCode}`,
  troughCode,
  sheetCount: workflowPlan?.defaultSheetCount ?? 100,
  quality: workflowPlan?.quality ?? "NA",
  estimatedSheetCount: Math.max(
    1,
    Math.round((workflowPlan?.defaultSheetCount ?? 100) * 0.98),
  ),
  createdAt: "Vừa scan",
});

const getEstimatedCutSheetCount = (sheetCount: number) =>
  Math.max(1, Math.round(sheetCount * 0.98));

export const TroughStation = ({
  mode,
  workflowPlan,
  troughBatches,
  cutBatches,
  rolledBatches,
  onCreateCutBatch,
  onCreateRolledBatch,
}: TroughStationProps) => {
  const [cutForm] = Form.useForm<CutFormValues>();
  const [selectedCutTrough, setSelectedCutTrough] =
    useState<TroughBatch | null>(null);
  const [selectedCutEyeCodes, setSelectedCutEyeCodes] = useState<string[]>(
    () => workflowPlan?.cutEyeCodes ?? [],
  );
  const [activeRollQuality, setActiveRollQuality] = useState<
    RssQuality | "all"
  >(workflowPlan?.quality ?? "all");
  const [selectedRollCuts, setSelectedRollCuts] = useState<CutBatch[]>([]);

  const selectedCutQuality =
    Form.useWatch("quality", cutForm) ??
    selectedCutTrough?.quality ??
    workflowPlan?.quality ??
    "NA";

  useEffect(() => {
    if (!workflowPlan) return;

    const planSource =
      troughBatches.find(
        (batch) => batch.troughCode === workflowPlan.troughCodes[0],
      ) ??
      getFallbackTroughBatch(
        workflowPlan.troughCodes[0] ?? "M-NA",
        workflowPlan,
      );

    setSelectedCutTrough(planSource);
    setSelectedCutEyeCodes(workflowPlan.cutEyeCodes);
    setActiveRollQuality(workflowPlan.quality);
    cutForm.setFieldsValue({
      sourceTroughBatchId: planSource.id,
      sheetCount: workflowPlan.defaultSheetCount,
      estimatedSheetCount: getEstimatedCutSheetCount(
        workflowPlan.defaultSheetCount,
      ),
      quality: planSource.quality,
    });
  }, [cutForm, troughBatches, workflowPlan]);

  const cutTroughSources = useMemo(() => {
    const sourceMap = new Map<string, TroughBatch>();

    troughBatches.forEach((batch) => sourceMap.set(batch.troughCode, batch));
    workflowPlan?.troughCodes.forEach((troughCode) => {
      if (!sourceMap.has(troughCode)) {
        sourceMap.set(
          troughCode,
          getFallbackTroughBatch(troughCode, workflowPlan),
        );
      }
    });

    return Array.from(sourceMap.values());
  }, [troughBatches, workflowPlan]);

  const cutTroughOptions = useMemo(
    () =>
      cutTroughSources.map((batch) => ({
        value: batch.id,
        label: batch.troughCode,
        description: `${batch.volumeLiters} lít · ${getRssQualityLabel(
          batch.quality,
        )}`,
        meta: workflowPlan?.troughCodes.includes(batch.troughCode)
          ? "Theo lệnh"
          : batch.createdAt,
      })),
    [cutTroughSources, workflowPlan],
  );

  const cutEyeOptions = useMemo(() => {
    const planSet = new Set(workflowPlan?.cutEyeCodes ?? []);

    return RSS_CUT_EYE_OPTIONS.map((cutEyeCode) => ({
      value: cutEyeCode,
      label: cutEyeCode.replace("Máy ", "#"),
      description: planSet.has(cutEyeCode) ? "Có trong lệnh" : "Sẵn sàng",
      meta: planSet.has(cutEyeCode) ? "Theo lệnh" : undefined,
    }));
  }, [workflowPlan]);

  const filteredCutBatches = useMemo(
    () =>
      activeRollQuality === "all"
        ? cutBatches
        : cutBatches.filter((batch) => batch.quality === activeRollQuality),
    [activeRollQuality, cutBatches],
  );

  const rollCutOptions = useMemo(
    () =>
      filteredCutBatches.map((batch) => ({
        value: batch.id,
        label: `Mương ${batch.troughCode}`,
        description: `${batch.sheetCount} tờ · ${getRssQualityLabel(
          batch.quality,
        )}`,
        meta:
          batch.estimatedSheetCount !== undefined
            ? `Dự tính ${batch.estimatedSheetCount} tờ sau cán`
            : batch.createdAt,
      })),
    [filteredCutBatches],
  );

  const selectedRollCutIds = useMemo(
    () => selectedRollCuts.map((batch) => batch.id),
    [selectedRollCuts],
  );

  const selectCutTrough = (source: TroughBatch) => {
    setSelectedCutTrough(source);
    cutForm.setFieldsValue({
      sourceTroughBatchId: source.id,
      sheetCount: workflowPlan?.defaultSheetCount ?? 100,
      estimatedSheetCount: getEstimatedCutSheetCount(
        workflowPlan?.defaultSheetCount ?? 100,
      ),
      quality: source.quality,
    });
    message.success(`Đã chọn mương ${source.troughCode} để cắt tờ`);
  };

  const handleScanCutTrough = (value: string) => {
    const found = troughBatches.find(
      (batch) => batch.troughCode.toLowerCase() === value.toLowerCase(),
    );
    selectCutTrough(found ?? getFallbackTroughBatch(value, workflowPlan));
  };

  const handleScanRollTrough = (value: string) => {
    const found = cutBatches.find(
      (batch) => batch.troughCode.toLowerCase() === value.toLowerCase(),
    );
    const source = found ?? getFallbackCutBatch(value, workflowPlan);

    setSelectedRollCuts((prev) =>
      prev.some((batch) => batch.id === source.id) ? prev : [...prev, source],
    );
    message.success(`Đã thêm mương ${source.troughCode} vào danh sách cán`);
  };

  const handleCutFinish = (values: CutFormValues) => {
    if (selectedCutEyeCodes.length === 0) {
      message.error("Vui lòng chọn ít nhất một Máy cắt");
      return;
    }

    const source =
      selectedCutTrough ??
      troughBatches.find((batch) => batch.id === values.sourceTroughBatchId) ??
      getFallbackTroughBatch(
        values.sourceTroughBatchId || "M-NA",
        workflowPlan,
      );

    const batch: CutBatch = {
      id: createWorkflowCode(`CUT-${source.troughCode}`),
      sourceTroughBatchId: source.id,
      troughCode: source.troughCode,
      cutEyeCodes: selectedCutEyeCodes,
      sheetCount: values.sheetCount,
      quality: values.quality,
      estimatedSheetCount: values.estimatedSheetCount,
      createdAt: "Vừa tạo",
    };

    onCreateCutBatch(batch);
    cutForm.resetFields();
    setSelectedCutTrough(null);
    setSelectedCutEyeCodes(workflowPlan?.cutEyeCodes ?? []);
    message.success(
      `Đã cắt ${values.sheetCount} tờ từ mương ${source.troughCode}`,
    );
  };

  const handleSelectRollCuts = (cutBatchIds: string[]) => {
    const sourceMap = new Map(cutBatches.map((batch) => [batch.id, batch]));

    setSelectedRollCuts(
      cutBatchIds
        .map((cutBatchId) => sourceMap.get(cutBatchId))
        .filter((batch): batch is CutBatch => Boolean(batch)),
    );
  };

  const handleRollFinish = () => {
    if (selectedRollCuts.length === 0) {
      message.error("Vui lòng chọn ít nhất một mương đã cắt");
      return;
    }

    selectedRollCuts.forEach((source, index) => {
      onCreateRolledBatch({
        id: createWorkflowCode(`ROLL-${source.troughCode}-${index + 1}`),
        sourceCutBatchId: source.id,
        troughCode: source.troughCode,
        sheetCount: source.sheetCount,
        quality: source.quality,
        estimatedSheetCount: Math.max(1, Math.round(source.sheetCount * 0.97)),
        createdAt: "Vừa tạo",
      });
    });

    message.success(
      `Đã cán ${selectedRollCuts.length} mương: ${selectedRollCuts
        .map((batch) => batch.troughCode)
        .join(", ")}`,
    );
    setSelectedRollCuts([]);
  };

  const isCutMode = mode === "cut";
  const title = isCutMode ? "Cắt tờ theo mương" : "Cán tờ theo chất lượng";
  const icon = isCutMode ? (
    <ScissorOutlined style={{ color: "#1677ff" }} />
  ) : (
    <DeploymentUnitOutlined style={{ color: "#52c41a" }} />
  );

  return (
    <Card
      className="rss-station-card"
      title={
        <Space>
          {icon}
          <span>{title}</span>
        </Space>
      }>
      <Form
        form={cutForm}
        layout="vertical"
        onFinish={handleCutFinish}
        size="large">
        {isCutMode ? (
          <Space orientation="vertical" style={{ width: "100%" }} size="large">
            <Card size="small" title="Chọn mương và Máy cắt tờ">
              <Space
                orientation="vertical"
                style={{ width: "100%" }}
                size="middle">
                <QrScannerInput
                  placeholder="Quét mã QR mương cần cắt…"
                  onScan={handleScanCutTrough}
                />

                <RssTilePicker
                  ariaLabel="Chọn mương cần cắt"
                  selectedValue={selectedCutTrough?.id}
                  options={cutTroughOptions}
                  onChange={(value) => {
                    const source = cutTroughSources.find(
                      (batch) => batch.id === value,
                    );
                    if (source) selectCutTrough(source);
                  }}
                />

                <Form.Item
                  name="sourceTroughBatchId"
                  rules={[{ required: true, message: "Vui lòng chọn mương" }]}
                  hidden>
                  <input type="hidden" />
                </Form.Item>

                <Form.Item
                  name="quality"
                  rules={[
                    { required: true, message: "Vui lòng chọn chất lượng" },
                  ]}
                  hidden>
                  <input type="hidden" />
                </Form.Item>

                {selectedCutTrough ? (
                  <Card
                    size="small"
                    style={{ marginBottom: 16, backgroundColor: "#fafafa" }}>
                    <Flex
                      align="center"
                      justify="space-between"
                      gap="small"
                      wrap>
                      <Space orientation="vertical" size={0}>
                        <Text strong>Mương {selectedCutTrough.troughCode}</Text>
                        <Text type="secondary">
                          {selectedCutTrough.volumeLiters} lít ·{" "}
                          {getRssQualityLabel(selectedCutTrough.quality)}
                        </Text>
                      </Space>
                      <Tag color="blue">{selectedCutTrough.id}</Tag>
                    </Flex>
                  </Card>
                ) : null}

                <Form.Item label="Máy cắt tờ" required>
                  <RssTilePicker
                    ariaLabel="Chọn máy cắt tờ"
                    multiple
                    selectedValues={selectedCutEyeCodes}
                    options={cutEyeOptions}
                    onChange={(values) =>
                      setSelectedCutEyeCodes(values as string[])
                    }
                  />
                  <RssSelectedSummary
                    label="Máy cắt đã chọn"
                    values={selectedCutEyeCodes}
                  />
                </Form.Item>

                <Form.Item
                  label="Số tờ sau khi cắt"
                  name="sheetCount"
                  rules={[{ required: true, message: "Vui lòng nhập số tờ" }]}>
                  <InputNumber min={1} style={{ width: "100%" }} />
                </Form.Item>

                <Form.Item label="Chất lượng sau cắt" required>
                  <RssQualityPicker
                    value={selectedCutQuality}
                    onChange={(quality) => cutForm.setFieldsValue({ quality })}
                  />
                </Form.Item>

                <Form.Item
                  label="Sản lượng dự tính sau cắt"
                  name="estimatedSheetCount"
                  rules={[
                    {
                      required: true,
                      message: "Vui lòng nhập sản lượng dự tính",
                    },
                  ]}>
                  <InputNumber min={1} style={{ width: "100%" }} />
                </Form.Item>

                <Button
                  type="primary"
                  htmlType="submit"
                  block
                  className="rss-mobile-primary-action">
                  Ghi nhận cắt tờ
                </Button>
              </Space>
            </Card>

            <RssTechnicalDetails
              summary={`Danh sách đã cắt (${cutBatches.length})`}>
              <BatchList emptyText="Chưa có lô tờ đã cắt" data={cutBatches} />
            </RssTechnicalDetails>
          </Space>
        ) : (
          <Space orientation="vertical" style={{ width: "100%" }} size="large">
            <Card size="small" title="Chọn chất lượng để cán">
              <Space
                orientation="vertical"
                style={{ width: "100%" }}
                size="middle">
                <RssQualityFlowPicker
                  value={activeRollQuality}
                  onChange={setActiveRollQuality}
                />
                <Text type="secondary">
                  Đang xử lý: {formatQualityFlowLabel(activeRollQuality)}
                </Text>
              </Space>
            </Card>

            <Card size="small" title="Chọn mương đã cắt để cán tờ">
              <Space
                orientation="vertical"
                style={{ width: "100%" }}
                size="middle">
                <QrScannerInput
                  placeholder="Quét mã QR mương cần cán…"
                  onScan={handleScanRollTrough}
                />

                <RssTilePicker
                  ariaLabel="Chọn mương đã cắt"
                  multiple
                  selectedValues={selectedRollCutIds}
                  options={rollCutOptions}
                  emptyText="Chưa có mương đã cắt theo này"
                  onChange={(values) =>
                    handleSelectRollCuts(values as string[])
                  }
                />

                <Card
                  size="small"
                  title={`Mương chờ cán (${selectedRollCuts.length})`}
                  style={{ backgroundColor: "#fafafa" }}>
                  {selectedRollCuts.length === 0 ? (
                    <Empty
                      image={Empty.PRESENTED_IMAGE_SIMPLE}
                      description="Chưa chọn mương nào"
                    />
                  ) : (
                    <Flex wrap="wrap" gap="small">
                      {selectedRollCuts.map((batch) => (
                        <Tag
                          key={batch.id}
                          color="green"
                          closable
                          onClose={() =>
                            setSelectedRollCuts((prev) =>
                              prev.filter((item) => item.id !== batch.id),
                            )
                          }
                          style={{ padding: "4px 8px" }}>
                          Mương {batch.troughCode} · {batch.sheetCount} tờ ·{" "}
                          {getRssQualityLabel(batch.quality)}
                        </Tag>
                      ))}
                    </Flex>
                  )}
                </Card>

                <Form.Item label="Sản lượng dự tính sau cán">
                  <InputNumber min={1} style={{ width: "100%" }} />
                </Form.Item>

                <Button
                  type="primary"
                  block
                  onClick={handleRollFinish}
                  className="rss-mobile-primary-action">
                  Ghi nhận cán {selectedRollCuts.length || ""} mương
                </Button>
              </Space>
            </Card>

            <RssTechnicalDetails
              summary={`Danh sách đã cán (${rolledBatches.length})`}>
              <BatchList
                emptyText="Chưa có lô tờ đã cán"
                data={rolledBatches}
              />
            </RssTechnicalDetails>
          </Space>
        )}
      </Form>
    </Card>
  );
};

interface BatchListProps {
  emptyText: string;
  data: Array<CutBatch | RolledBatch>;
}

const BatchList = ({ emptyText, data }: BatchListProps) => {
  const groupedBatches = data.reduce<
    Record<string, Array<CutBatch | RolledBatch>>
  >((groups, item) => {
    const key = item.quality;
    groups[key] = [...(groups[key] ?? []), item];
    return groups;
  }, {});

  return (
    <Card size="small" title="Danh sách theo chất lượng">
      {data.length === 0 ? (
        <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={emptyText} />
      ) : (
        <Space orientation="vertical" style={{ width: "100%" }} size="middle">
          {Object.entries(groupedBatches).map(([quality, batches]) => (
            <Card
              key={quality}
              size="small"
              title={`Luồng ${getRssQualityLabel(quality as RssQuality)}`}
              extra={<Tag color="blue">{batches.length} lô</Tag>}>
              <div className="rss-record-list">
                {batches.map((item) => (
                  <div key={item.id} className="rss-touch-list-item">
                    <Flex
                      align="center"
                      justify="space-between"
                      gap="small"
                      wrap>
                      <Space orientation="vertical" size={0}>
                        <Text strong>
                          <BarcodeOutlined /> {item.id}
                        </Text>
                        <Text type="secondary">
                          Mương {item.troughCode} · {item.sheetCount} tờ ·{" "}
                          {item.createdAt}
                        </Text>
                      </Space>
                      <Space size={4} wrap>
                        {"cutEyeCodes" in item && item.cutEyeCodes?.length ? (
                          <Tag color="cyan">
                            {item.cutEyeCodes.join(", ")}
                          </Tag>
                        ) : null}
                        <Tag color="green">
                          Dự tính {item.estimatedSheetCount ?? item.sheetCount}{" "}
                          tờ
                        </Tag>
                        <Tag color="blue">
                          {getRssQualityLabel(item.quality)}
                        </Tag>
                      </Space>
                    </Flex>
                  </div>
                ))}
              </div>
            </Card>
          ))}
        </Space>
      )}
    </Card>
  );
};
