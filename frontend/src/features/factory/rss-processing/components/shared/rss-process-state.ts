export type RssQuality = "L1" | "L2" | "L3" | "Mix" | "NA";

export const RSS_QUALITY_OPTIONS: Array<{
  value: RssQuality;
  label: string;
  desc: string;
}> = [
  { value: "L1", label: "L1", desc: "Chất lượng cao" },
  { value: "L2", label: "L2", desc: "Chất lượng trung bình" },
  { value: "L3", label: "L3", desc: "Chất lượng thấp" },
  { value: "Mix", label: "Mix (trộn)", desc: "Nguồn tờ được trộn" },
  { value: "NA", label: "NA", desc: "Chưa phân loại" },
];

export const getRssQualityLabel = (quality: RssQuality) =>
  RSS_QUALITY_OPTIONS.find((item) => item.value === quality)?.label ?? quality;

export interface TroughBatch {
  id: string;
  tankCode: string;
  troughCode: string;
  volumeLiters: number;
  quality: RssQuality;
  drc?: number;
  ph?: number;
  createdAt: string;
}

export interface CutBatch {
  id: string;
  sourceTroughBatchId: string;
  troughCode: string;
  cutEyeCodes?: string[];
  sheetCount: number;
  quality: RssQuality;
  estimatedSheetCount?: number;
  createdAt: string;
}

export interface RolledBatch {
  id: string;
  sourceCutBatchId: string;
  troughCode: string;
  sheetCount: number;
  quality: RssQuality;
  estimatedSheetCount?: number;
  createdAt: string;
}

export interface PoleAssignment {
  id: string;
  poleCode: string;
  troughCode: string;
  rolledBatchId: string;
  sheetCount: number;
  quality: RssQuality;
}

export interface TrolleyBatch {
  trolleyCode: string;
  assignments: PoleAssignment[];
  status: "loaded" | "drying" | "dried";
  createdAt: string;
  ovenCode?: string;
  driedAt?: string;
}

export interface DriedPole extends PoleAssignment {
  trolleyCode: string;
  ovenCode: string;
  grade: RssQuality;
  driedWeightKg: number;
  driedAt: string;
}

export interface BaleDraft {
  draftCode: string;
  baleCount: number;
  grade: RssQuality;
  sourcePoleIds: string[];
  productTypeId?: number;
  createdAt: string;
}

export interface RssProcessState {
  troughBatches: TroughBatch[];
  cutBatches: CutBatch[];
  rolledBatches: RolledBatch[];
  trolleyBatches: TrolleyBatch[];
  driedPoles: DriedPole[];
  baleDrafts: BaleDraft[];
}

export const createWorkflowCode = (prefix: string) =>
  `${prefix}-${Date.now().toString().slice(-6)}`;

export const getDriedPoleKey = (pole: Pick<DriedPole, "trolleyCode" | "id">) =>
  `${pole.trolleyCode}:${pole.id}`;

export const RSS_TROUGH_OPTIONS = Array.from({ length: 12 }, (_, index) => {
  const number = String(index + 1).padStart(2, "0");
  return `M-${number}`;
});

export const RSS_CUT_EYE_OPTIONS = Array.from({ length: 10 }, (_, index) => {
  const number = String(index + 1).padStart(2, "0");
  return `Máy ${number}`;
});

export const RSS_POLE_OPTIONS = Array.from({ length: 12 }, (_, index) => {
  const number = String(index + 1).padStart(2, "0");
  return `Sào ${number}`;
});

export const RSS_OVEN_OPTIONS = ["LO-01", "LO-02", "LO-03"];

export interface RssWorkflowPlan {
  id: string;
  orderCode: string;
  orderName: string;
  productName: string;
  productionDate?: string;
  status?: string;
  requiredQuantityKg?: number;
  tankCode: string;
  trolleyCode: string;
  ovenCode: string;
  troughCodes: string[];
  cutEyeCodes: string[];
  poleCodes: string[];
  quality: RssQuality;
  defaultVolumeLiters: number;
  defaultSheetCount: number;
}

export const RSS_SAMPLE_WORKFLOW_PLANS: RssWorkflowPlan[] = [
  {
    id: "rss-plan-001",
    orderCode: "LSX-RSS-001",
    orderName: "RSS mẫu - Quy trình L1",
    productName: "RSS 3",
    productionDate: "2026-06-10",
    status: "in_production",
    requiredQuantityKg: 1200,
    tankCode: "Bồn 1",
    trolleyCode: "GG-01",
    ovenCode: "LO-01",
    troughCodes: ["M-01", "M-02", "M-03"],
    cutEyeCodes: ["Máy 01", "Máy 02", "Máy 03"],
    poleCodes: ["Sào 01", "Sào 02", "Sào 03", "Sào 04"],
    quality: "L1",
    defaultVolumeLiters: 220,
    defaultSheetCount: 120,
  },
  {
    id: "rss-plan-002",
    orderCode: "LSX-RSS-002",
    orderName: "RSS mẫu - Quy trình L2",
    productName: "RSS 5",
    productionDate: "2026-06-10",
    status: "approved",
    requiredQuantityKg: 900,
    tankCode: "Bồn 2",
    trolleyCode: "GG-02",
    ovenCode: "LO-02",
    troughCodes: ["M-04", "M-05"],
    cutEyeCodes: ["Máy 04", "Máy 05"],
    poleCodes: ["Sào 05", "Sào 06", "Sào 07"],
    quality: "L2",
    defaultVolumeLiters: 200,
    defaultSheetCount: 110,
  },
];

export const createRssProcessStateFromPlan = (
  plan: RssWorkflowPlan,
): RssProcessState => {
  const troughBatches = plan.troughCodes.map<TroughBatch>((troughCode) => ({
    id: `${plan.orderCode}-TRB-${troughCode}`,
    tankCode: plan.tankCode,
    troughCode,
    volumeLiters: plan.defaultVolumeLiters,
    quality: plan.quality,
    drc: 24,
    ph: 5,
    createdAt: "Theo lệnh",
  }));

  const cutBatches = troughBatches.map<CutBatch>((batch, index) => ({
    id: `${plan.orderCode}-CUT-${batch.troughCode}`,
    sourceTroughBatchId: batch.id,
    troughCode: batch.troughCode,
    cutEyeCodes:
      plan.cutEyeCodes[index] !== undefined ? [plan.cutEyeCodes[index]] : [],
    sheetCount: plan.defaultSheetCount,
    quality: plan.quality,
    estimatedSheetCount: Math.round(plan.defaultSheetCount * 0.98),
    createdAt: "Theo lệnh",
  }));

  const rolledBatches = cutBatches.map<RolledBatch>((batch) => ({
    id: `${plan.orderCode}-ROLL-${batch.troughCode}`,
    sourceCutBatchId: batch.id,
    troughCode: batch.troughCode,
    sheetCount: Math.max(1, Math.round(batch.sheetCount * 0.97)),
    quality: batch.quality,
    estimatedSheetCount: Math.max(1, Math.round(batch.sheetCount * 0.97)),
    createdAt: "Theo lệnh",
  }));

  const assignments = plan.poleCodes.map<PoleAssignment>((poleCode, index) => {
    const rolledBatch =
      rolledBatches[index % Math.max(1, rolledBatches.length)];

    return {
      id: `${plan.trolleyCode}-${poleCode}`,
      poleCode,
      troughCode: rolledBatch?.troughCode ?? plan.troughCodes[0] ?? "M-NA",
      rolledBatchId: rolledBatch?.id ?? `${plan.orderCode}-ROLL-DRAFT`,
      sheetCount: Math.max(
        1,
        Math.round(plan.defaultSheetCount / Math.max(1, plan.poleCodes.length)),
      ),
      quality: plan.quality,
    };
  });

  return {
    troughBatches,
    cutBatches,
    rolledBatches,
    trolleyBatches: [
      {
        trolleyCode: plan.trolleyCode,
        status: "loaded",
        createdAt: "Theo lệnh",
        assignments,
      },
    ],
    driedPoles: [],
    baleDrafts: [],
  };
};

export const INITIAL_RSS_PROCESS_STATE: RssProcessState = {
  troughBatches: [
    {
      id: "TRB-M01",
      tankCode: "Bồn 1",
      troughCode: "M-01",
      volumeLiters: 220,
      quality: "L1",
      drc: 24,
      ph: 5.1,
      createdAt: "Mẫu sẵn",
    },
    {
      id: "TRB-M02",
      tankCode: "Bồn 2",
      troughCode: "M-02",
      volumeLiters: 200,
      quality: "L2",
      drc: 23,
      ph: 5,
      createdAt: "Mẫu sẵn",
    },
    {
      id: "TRB-M03",
      tankCode: "Bồn 3",
      troughCode: "M-03",
      volumeLiters: 180,
      quality: "NA",
      drc: 22,
      ph: 5.2,
      createdAt: "Mẫu sẵn",
    },
  ],
  cutBatches: [
    {
      id: "CUT-M01",
      sourceTroughBatchId: "TRB-M01",
      troughCode: "M-01",
      sheetCount: 120,
      quality: "L1",
      createdAt: "Mẫu sẵn",
    },
    {
      id: "CUT-M02",
      sourceTroughBatchId: "TRB-M02",
      troughCode: "M-02",
      sheetCount: 110,
      quality: "L2",
      createdAt: "Mẫu sẵn",
    },
  ],
  rolledBatches: [
    {
      id: "ROLL-M01",
      sourceCutBatchId: "CUT-M01",
      troughCode: "M-01",
      sheetCount: 116,
      quality: "L1",
      createdAt: "Mẫu sẵn",
    },
  ],
  trolleyBatches: [
    {
      trolleyCode: "GG-01",
      status: "loaded",
      createdAt: "Mẫu sẵn",
      assignments: [
        {
          id: "GG-01-S01",
          poleCode: "Sào 01",
          troughCode: "M-01",
          rolledBatchId: "ROLL-M01",
          sheetCount: 58,
          quality: "L1",
        },
        {
          id: "GG-01-S02",
          poleCode: "Sào 02",
          troughCode: "M-01",
          rolledBatchId: "ROLL-M01",
          sheetCount: 58,
          quality: "L1",
        },
      ],
    },
    {
      trolleyCode: "GG-02",
      status: "drying",
      createdAt: "Mẫu sẵn",
      ovenCode: "LO-01",
      assignments: [
        {
          id: "GG-02-S01",
          poleCode: "Sào 01",
          troughCode: "M-02",
          rolledBatchId: "ROLL-M02",
          sheetCount: 52,
          quality: "L2",
        },
      ],
    },
  ],
  driedPoles: [
    {
      id: "GG-03-S01",
      trolleyCode: "GG-03",
      poleCode: "Sào 01",
      troughCode: "M-01",
      rolledBatchId: "ROLL-M01",
      sheetCount: 54,
      quality: "L1",
      ovenCode: "LO-02",
      grade: "L1",
      driedWeightKg: 74,
      driedAt: "Mẫu sẵn",
    },
    {
      id: "GG-03-S02",
      trolleyCode: "GG-03",
      poleCode: "Sào 02",
      troughCode: "M-02",
      rolledBatchId: "ROLL-M02",
      sheetCount: 50,
      quality: "L2",
      ovenCode: "LO-02",
      grade: "L2",
      driedWeightKg: 68,
      driedAt: "Mẫu sẵn",
    },
  ],
  baleDrafts: [],
};
