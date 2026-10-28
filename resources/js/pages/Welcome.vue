<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type OrderRow = {
    id: number;
    customer: string;
    city: string;
    status: string;
    total: string;
};

type AskResult = {
    ask: {
        id: number;
        question: string;
        generated_filter: Record<string, string | number>;
        answer: string;
        row_count: number;
    };
    rows: OrderRow[];
};

const props = defineProps<{
    orders: OrderRow[];
    orderCount: number;
    cities: string[];
    statusCounts: Record<string, number>;
    llmMode: string;
}>();

const suggestions = [
    'Shipped orders in Austin',
    'Orders over 100',
    'Delivered orders in Seattle',
];

const question = ref('');
const rows = ref([...props.orders]);
const result = ref<AskResult | null>(null);
const isLoading = ref(false);
const errorMessage = ref('');
const shippedCount = computed(() => props.statusCounts.shipped ?? 0);

async function askQuestion(value = question.value): Promise<void> {
    const trimmedQuestion = value.trim();

    if (!trimmedQuestion || isLoading.value) {
        return;
    }

    question.value = trimmedQuestion;
    errorMessage.value = '';
    isLoading.value = true;

    try {
        const csrfToken = document.querySelector<HTMLMetaElement>(
            'meta[name="csrf-token"]',
        )?.content;
        const response = await fetch('/ask', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            body: JSON.stringify({ question: trimmedQuestion }),
        });

        if (!response.ok) {
            throw new Error(
                response.status === 422
                    ? 'Enter a question under 1,000 characters.'
                    : 'The desk could not complete that request. Please try again.',
            );
        }

        const data = (await response.json()) as AskResult;
        result.value = data;
        rows.value = data.rows;
    } catch (error) {
        errorMessage.value =
            error instanceof Error
                ? error.message
                : 'Something went wrong. Please try again.';
    } finally {
        isLoading.value = false;
    }
}

function submitQuestion(event: SubmitEvent): void {
    event.preventDefault();
    void askQuestion();
}

function statusClass(status: string): string {
    return (
        {
            shipped: 'status-shipped',
            delivered: 'status-delivered',
            processing: 'status-processing',
            cancelled: 'status-cancelled',
        }[status] ?? 'status-processing'
    );
}

function currency(value: string): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(Number(value));
}

function filterLabel(key: string, value: string | number): string {
    if (key === 'min_total') {
        return `Over ${currency(String(value))}`;
    }

    if (key === 'city') {
        return `In ${value}`;
    }

    return String(value);
}
</script>

<template>
    <Head title="CSV Ask Desk">
        <meta
            name="description"
            content="Ask plain-English questions about a sample orders CSV and inspect every matching row."
        />
    </Head>

    <div class="desk-shell">
        <aside class="sidebar" aria-label="Workspace navigation">
            <a href="/" class="brand-lockup" aria-label="Harbor Desk home">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none">
                        <path
                            d="M6 22.5 12.5 16l4 4L26 10.5M19 10.5h7v7"
                            stroke="currentColor"
                            stroke-width="2.3"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </span>
                <span class="brand-copy">
                    <strong>Harbor Desk</strong>
                    <small>OPERATIONS</small>
                </span>
            </a>

            <div class="workspace-label">WORKSPACE</div>
            <nav class="side-nav">
                <a href="#ask" class="nav-link nav-link-active">
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path
                            d="M3.5 5.5h13m-13 4.5h13m-13 4.5h8"
                            stroke="currentColor"
                            stroke-width="1.6"
                            stroke-linecap="round"
                        />
                    </svg>
                    Ask your data
                    <span class="nav-dot"></span>
                </a>
                <a href="#orders" class="nav-link">
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path
                            d="M4 3.5h8l4 4v9H4v-13Z"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M12 3.5v4h4M7 11h6M7 14h6"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                        />
                    </svg>
                    Orders
                    <span class="nav-count">{{ orderCount }}</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="dataset-card">
                    <div class="dataset-card-top">
                        <span class="dataset-icon" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="none">
                                <path
                                    d="M3.5 5.2c0-1 2.9-1.8 6.5-1.8s6.5.8 6.5 1.8S13.6 7 10 7s-6.5-.8-6.5-1.8Zm0 0v9.6c0 1 2.9 1.8 6.5 1.8s6.5-.8 6.5-1.8V5.2M3.5 10c0 1 2.9 1.8 6.5 1.8s6.5-.8 6.5-1.8"
                                    stroke="currentColor"
                                    stroke-width="1.4"
                                />
                            </svg>
                        </span>
                        <span class="connected-dot"></span>
                    </div>
                    <strong>orders.csv</strong>
                    <span
                        >{{ orderCount }} rows ·
                        {{ cities.length }} cities</span
                    >
                    <div class="dataset-progress"><i></i></div>
                    <small>Sample dataset loaded</small>
                </div>

                <div class="profile-row">
                    <span class="avatar">HD</span>
                    <span class="profile-copy">
                        <strong>Harbor Desk</strong>
                        <small>Demo workspace</small>
                    </span>
                    <span class="profile-menu" aria-hidden="true">···</span>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="breadcrumbs">
                    <span>Workspace</span>
                    <span class="crumb-separator">/</span>
                    <strong>Ask your data</strong>
                </div>
                <div class="topbar-right">
                    <span class="mode-pill">
                        <span class="mode-indicator"></span>{{ llmMode }}
                    </span>
                    <button
                        class="help-button"
                        type="button"
                        aria-label="About this demo"
                    >
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <circle
                                cx="10"
                                cy="10"
                                r="7.25"
                                stroke="currentColor"
                                stroke-width="1.5"
                            />
                            <path
                                d="M8.2 7.6a1.9 1.9 0 1 1 3.4 1.2c-.9 1.1-1.6 1.1-1.6 2.5M10 14.1v.1"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                            />
                        </svg>
                    </button>
                </div>
            </header>

            <div class="page-wrap">
                <section class="welcome-row">
                    <div>
                        <div class="eyebrow">
                            <span></span> YOUR DATA, IN PLAIN ENGLISH
                        </div>
                        <h1>Good morning, team <span class="wave">✳</span></h1>
                        <p>
                            Ask a question. Get a clear answer backed by your
                            orders.
                        </p>
                    </div>
                    <div class="date-stamp">
                        <span class="date-icon" aria-hidden="true">◷</span>
                        <span
                            >Live dataset<br /><strong>orders.csv</strong></span
                        >
                    </div>
                </section>

                <section
                    id="ask"
                    class="ask-card"
                    aria-labelledby="ask-heading"
                >
                    <div class="ask-card-header">
                        <div class="ask-heading-group">
                            <span class="sparkle-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="m12 2 1.9 7.1L21 11l-7.1 1.9L12 20l-1.9-7.1L3 11l7.1-1.9L12 2Z"
                                        stroke="currentColor"
                                        stroke-width="1.6"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="m19 16 .8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8L19 16Z"
                                        fill="currentColor"
                                    />
                                </svg>
                            </span>
                            <div>
                                <h2 id="ask-heading">Ask your orders</h2>
                                <p>
                                    Questions work best with a status, city, or
                                    order value.
                                </p>
                            </div>
                        </div>
                        <span class="private-note"
                            ><span>⌑</span> Your data stays yours</span
                        >
                    </div>

                    <form class="question-form" @submit="submitQuestion">
                        <label class="sr-only" for="question"
                            >Ask a question about the orders</label
                        >
                        <span class="input-search-icon" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="none">
                                <circle
                                    cx="8.7"
                                    cy="8.7"
                                    r="5.7"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                />
                                <path
                                    d="m13 13 4 4"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>
                        <input
                            id="question"
                            v-model="question"
                            type="text"
                            maxlength="1000"
                            placeholder="Try “shipped orders in Austin”..."
                            autocomplete="off"
                            :disabled="isLoading"
                        />
                        <button
                            class="ask-button"
                            type="submit"
                            :disabled="isLoading || !question.trim()"
                        >
                            <span
                                v-if="isLoading"
                                class="button-spinner"
                            ></span>
                            <span>{{
                                isLoading ? 'Thinking' : 'Ask desk'
                            }}</span>
                            <svg
                                v-if="!isLoading"
                                viewBox="0 0 20 20"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M4 10h11m-4-4 4 4-4 4"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>
                    </form>

                    <div class="suggestion-row">
                        <span class="suggestion-label">TRY ASKING</span>
                        <button
                            v-for="suggestion in suggestions"
                            :key="suggestion"
                            type="button"
                            class="suggestion-chip"
                            :disabled="isLoading"
                            @click="askQuestion(suggestion)"
                        >
                            {{ suggestion }}
                            <svg
                                viewBox="0 0 16 16"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M4 8h8m-3-3 3 3-3 3"
                                    stroke="currentColor"
                                    stroke-width="1.3"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>
                    </div>
                    <p v-if="errorMessage" class="error-message" role="alert">
                        {{ errorMessage }}
                    </p>
                </section>

                <section v-if="result" class="answer-card" aria-live="polite">
                    <div class="answer-icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none">
                            <path
                                d="m4.5 10.2 3.4 3.4 7.6-7.8"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                    <div class="answer-copy">
                        <div class="answer-overline">
                            ANSWER <span>·</span> {{ result.ask.row_count }}
                            {{
                                result.ask.row_count === 1 ? 'MATCH' : 'MATCHES'
                            }}
                        </div>
                        <p>{{ result.ask.answer }}</p>
                        <div
                            v-if="
                                Object.keys(result.ask.generated_filter).length
                            "
                            class="filter-chips"
                        >
                            <span
                                v-for="(value, key) in result.ask
                                    .generated_filter"
                                :key="key"
                                class="filter-chip"
                            >
                                <span>{{
                                    key === 'min_total'
                                        ? '↗'
                                        : key === 'city'
                                          ? '⌖'
                                          : '◉'
                                }}</span
                                >{{ filterLabel(String(key), value) }}
                            </span>
                        </div>
                        <div class="question-echo">
                            “{{ result.ask.question }}”
                        </div>
                    </div>
                    <div class="answer-confidence">
                        <span></span> Filtered safely
                    </div>
                </section>

                <section
                    class="orders-section"
                    id="orders"
                    aria-labelledby="orders-heading"
                >
                    <div class="section-heading-row">
                        <div>
                            <div class="section-overline">YOUR DATASET</div>
                            <h2 id="orders-heading">
                                {{
                                    result
                                        ? 'Matching orders'
                                        : 'Orders overview'
                                }}
                            </h2>
                        </div>
                        <div class="table-actions">
                            <span class="updated-label"
                                ><span></span> Just updated</span
                            >
                            <button
                                v-if="result"
                                class="reset-button"
                                type="button"
                                @click="
                                    result = null;
                                    rows = [...props.orders];
                                    question = '';
                                    errorMessage = '';
                                "
                            >
                                Clear filters
                            </button>
                            <button
                                v-else
                                class="export-button"
                                type="button"
                                disabled
                                title="Export is not part of this demo"
                            >
                                <svg
                                    viewBox="0 0 18 18"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M9 2.8v8m0 0 3-3m-3 3-3-3M3.5 11.5v3h11v-3"
                                        stroke="currentColor"
                                        stroke-width="1.4"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                                Export
                            </button>
                        </div>
                    </div>

                    <div class="stats-row">
                        <div class="stat-card">
                            <span
                                class="stat-icon stat-icon-ink"
                                aria-hidden="true"
                                >▤</span
                            >
                            <span class="stat-copy"
                                ><small>TOTAL ORDERS</small
                                ><strong>{{
                                    result ? result.ask.row_count : orderCount
                                }}</strong></span
                            >
                            <span class="stat-footnote">{{
                                result
                                    ? 'matching your query'
                                    : 'across all cities'
                            }}</span>
                        </div>
                        <div class="stat-card">
                            <span
                                class="stat-icon stat-icon-green"
                                aria-hidden="true"
                                >↗</span
                            >
                            <span class="stat-copy"
                                ><small>SHIPPED</small
                                ><strong>{{ shippedCount }}</strong></span
                            >
                            <span class="stat-footnote"
                                ><span class="mini-status-dot"></span> orders on
                                the way</span
                            >
                        </div>
                        <div class="stat-card">
                            <span
                                class="stat-icon stat-icon-gold"
                                aria-hidden="true"
                                >⌖</span
                            >
                            <span class="stat-copy"
                                ><small>CITIES</small
                                ><strong>{{ cities.length }}</strong></span
                            >
                            <span class="stat-footnote"
                                >delivery destinations</span
                            >
                        </div>
                    </div>

                    <div class="table-card">
                        <div class="table-card-header">
                            <div>
                                <strong
                                    >{{ rows.length }}
                                    {{
                                        rows.length === 1 ? 'order' : 'orders'
                                    }}</strong
                                >
                                <span>{{
                                    result
                                        ? 'returned by your question'
                                        : 'in the Harbor Desk sample'
                                }}</span>
                            </div>
                            <span class="table-source"
                                ><span></span> orders.csv</span
                            >
                        </div>
                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th scope="col">ORDER</th>
                                        <th scope="col">CUSTOMER</th>
                                        <th scope="col">CITY</th>
                                        <th scope="col">STATUS</th>
                                        <th scope="col" class="amount-cell">
                                            TOTAL
                                        </th>
                                    </tr>
                                </thead>
                                <tbody v-if="rows.length">
                                    <tr v-for="order in rows" :key="order.id">
                                        <td class="order-id">
                                            #{{ order.id }}
                                        </td>
                                        <td class="customer-cell">
                                            <span class="customer-avatar">{{
                                                order.customer
                                                    .split(' ')
                                                    .map((part) => part[0])
                                                    .join('')
                                            }}</span>
                                            <strong>{{
                                                order.customer
                                            }}</strong>
                                        </td>
                                        <td class="city-cell">
                                            <span class="city-pin">⌖</span
                                            >{{ order.city }}
                                        </td>
                                        <td>
                                            <span
                                                class="status-badge"
                                                :class="
                                                    statusClass(order.status)
                                                "
                                                ><i></i>{{ order.status }}</span
                                            >
                                        </td>
                                        <td class="amount-cell">
                                            {{ currency(order.total) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr>
                                        <td colspan="5" class="empty-state">
                                            <span class="empty-state-icon"
                                                >⌕</span
                                            >
                                            <strong>No matching orders</strong>
                                            <span
                                                >Try another city, status, or
                                                order value.</span
                                            >
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-footer">
                            <span
                                >Showing
                                <strong
                                    >{{ rows.length ? 1 : 0 }}–{{
                                        rows.length
                                    }}</strong
                                >
                                of <strong>{{ rows.length }}</strong>
                                {{
                                    rows.length === 1 ? 'result' : 'results'
                                }}</span
                            >
                            <span class="safe-query"
                                ><span>✓</span> Read-only query</span
                            >
                        </div>
                    </div>
                </section>

                <footer class="page-footer">
                    <span
                        ><strong>CSV Ask Desk</strong> · A small, safe AI data
                        demo</span
                    >
                    <span>Built for questions, not SQL.</span>
                </footer>
            </div>
        </main>
    </div>
</template>
