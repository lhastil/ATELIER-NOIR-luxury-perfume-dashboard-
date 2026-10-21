<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardComponentsTest extends TestCase
{
    public function test_stat_card_shows_a_value(): void
    {
        $this->blade('<x-stat-card label="Total Orders" value="12" hint="Since opening" />')
            ->assertSee('<p class="stat-card__value">12</p>', false)
            ->assertSee('Since opening')
            ->assertDontSee('No data');
    }

    public function test_explicit_ids_are_kept(): void
    {
        $html = (string) $this->blade('<x-stat-card label="Orders" label-id="kpi-orders" />');

        $this->assertStringContainsString('aria-labelledby="kpi-orders"', $html);
        $this->assertStringContainsString('<p id="kpi-orders"', $html);
        $this->assertReferencesResolve($html);
    }

    public function test_labels_without_letters_or_digits_fall_back_to_a_valid_id(): void
    {
        // A punctuation-only label slugs to an empty string, so the fallback path is used.
        $html = (string) $this->blade(
            "<x-stat-card label=\"— · —\" />\n<x-card title=\"!!!\">Body</x-card>\n<x-table caption=\"???\" :columns=\"['A']\" />"
        );

        preg_match_all('#\sid="([^"]+)"#', $html, $ids);

        $this->assertSame(['stat-item', 'card-item', 'table-item'], $ids[1]);
        foreach ($ids[1] as $id) {
            $this->assertMatchesRegularExpression('/^[A-Za-z][A-Za-z0-9_-]*$/', $id);
        }
        $this->assertReferencesResolve($html);
    }

    public function test_repeated_labels_get_distinct_ids_each_wired_to_its_own_component(): void
    {
        $html = (string) $this->blade(
            "<x-stat-card label=\"Orders\" />\n<x-stat-card label=\"Orders\" />\n"
            ."<x-card title=\"Orders\">A</x-card>\n<x-card title=\"Orders\">B</x-card>\n"
            ."<x-table caption=\"Orders\" :columns=\"['A']\" />\n<x-table caption=\"Orders\" :columns=\"['A']\" />"
        );

        foreach (['stat-orders', 'stat-orders-2'] as $id) {
            $this->assertMatchesRegularExpression('#role="group" aria-labelledby="'.$id.'">\s*<p id="'.$id.'" class="stat-card__label">#', $html);
        }
        foreach (['card-orders', 'card-orders-2'] as $id) {
            $this->assertMatchesRegularExpression('#<section class="card" aria-labelledby="'.$id.'">\s*<header class="card__header">\s*<div class="card__heading">\s*<h2 id="'.$id.'"#', $html);
        }
        foreach (['table-orders', 'table-orders-2'] as $id) {
            $this->assertMatchesRegularExpression('#aria-labelledby="'.$id.'"[^>]*>\s*<table class="table">\s*<caption id="'.$id.'"#', $html);
        }
        $this->assertReferencesResolve($html);
    }

    public function test_a_generated_id_skips_an_explicit_id_reserved_earlier_on_the_page(): void
    {
        $html = (string) $this->blade("<x-stat-card label=\"Orders\" label-id=\"stat-orders\" />\n<x-stat-card label=\"Orders\" />");

        preg_match_all('#\sid="([^"]+)"#', $html, $ids);

        $this->assertSame(['stat-orders', 'stat-orders-2'], $ids[1]);
    }

    public function test_ids_are_the_same_on_every_render(): void
    {
        $template = "<x-stat-card label=\"Orders\" />\n<x-stat-card label=\"Orders\" />";

        $this->assertSame((string) $this->blade($template), (string) $this->blade($template));
    }

    public function test_stat_card_without_a_value_announces_no_data(): void
    {
        $this->blade('<x-stat-card label="Total Orders" />')
            ->assertSee('role="group" aria-labelledby="stat-total-orders"', false)
            ->assertSee('<p id="stat-total-orders" class="stat-card__label">Total Orders</p>', false)
            ->assertSee('<span aria-hidden="true">—</span><span class="visually-hidden">No data</span>', false)
            ->assertDontSee('stat-card__hint');
    }

    public function test_card_renders_a_labelled_section(): void
    {
        $this->blade('<x-card title="Best Sellers" description="Top fragrances">Body</x-card>')
            ->assertSee('<section class="card" aria-labelledby="card-best-sellers">', false)
            ->assertSee('<h2 id="card-best-sellers" class="card__title">Best Sellers</h2>', false)
            ->assertSee('<p class="card__description">Top fragrances</p>', false)
            ->assertSee('Body')
            ->assertDontSee('card__actions');
    }

    public function test_card_renders_actions_and_a_custom_heading_id(): void
    {
        $this->blade("<x-card title=\"Orders\" heading-id=\"orders-heading\"><x-slot:actions><a href=\"/orders\">All</a></x-slot:actions>\nBody</x-card>")
            ->assertSee('aria-labelledby="orders-heading"', false)
            ->assertSee('<div class="card__actions"><a href="/orders">All</a></div>', false);
    }

    public function test_chart_panel_reserves_a_chart_area(): void
    {
        $this->blade('<x-chart-panel title="Revenue">Empty</x-chart-panel>')
            ->assertSee('<section class="card chart-panel" aria-labelledby="card-revenue">', false)
            ->assertSee('class="chart-panel__area"', false);
    }

    public function test_empty_state_renders_title_and_text(): void
    {
        $this->blade('<x-empty-state icon="award" title="Nothing yet">Explanation</x-empty-state>')
            ->assertSee('<p class="empty-state__title">Nothing yet</p>', false)
            ->assertSee('<div class="empty-state__text">Explanation</div>', false)
            ->assertSee('aria-hidden="true"', false);
    }

    public function test_table_shows_the_empty_slot_only_without_rows(): void
    {
        $template = "<x-table caption=\"Orders\" :columns=\"['Order', ['label' => 'Total', 'numeric' => true], ['label' => 'Actions', 'hidden' => true]]\">%s<x-slot:empty><p>None</p></x-slot:empty>\n</x-table>";

        $this->blade(sprintf($template, ''))
            ->assertSee('<th scope="col" class="is-numeric">', false)
            ->assertSee('<span class="visually-hidden">Actions</span>', false)
            ->assertSee('<p>None</p>', false);

        $this->blade(sprintf($template, '<tr><td>A-1</td><td>1</td><td></td></tr>'))
            ->assertSee('<td>A-1</td>', false)
            ->assertDontSee('<p>None</p>', false)
            ->assertDontSee('table-region--empty');

        // A comment is not a row, so the empty state still shows.
        $this->blade(sprintf($template, '<!-- no rows -->'))
            ->assertSee('<p>None</p>', false)
            ->assertSee('table-region--empty');
    }

    public function test_component_text_is_escaped(): void
    {
        $this->blade('<x-stat-card :label="$label" :value="$label" :hint="$label" />', ['label' => '<script>x</script>'])
            ->assertDontSee('<script>x</script>', false)
            ->assertSee('&lt;script&gt;x&lt;/script&gt;', false);

        $this->blade('<x-card :title="$title" :description="$title">Body</x-card>', ['title' => '<b>x</b>'])
            ->assertDontSee('<b>x</b>', false);

        $this->blade('<x-chart-panel :title="$title" :description="$title">Body</x-chart-panel>', ['title' => '<b>x</b>'])
            ->assertDontSee('<b>x</b>', false);

        $this->blade('<x-empty-state :title="$title">{{ $title }}</x-empty-state>', ['title' => '<b>x</b>'])
            ->assertDontSee('<b>x</b>', false);

        $this->blade('<x-table :caption="$title" :columns="[$title]" />', ['title' => '"><b>x</b>'])
            ->assertDontSee('<b>x</b>', false)
            ->assertDontSee('"><b>', false);
    }

    /**
     * Every aria-labelledby reference names exactly one element with that id.
     */
    private function assertReferencesResolve(string $html): void
    {
        preg_match_all('#\sid="([^"]+)"#', $html, $ids);
        preg_match_all('#aria-labelledby="([^"]+)"#', $html, $references);

        $this->assertNotEmpty($references[1]);
        $this->assertSame($ids[1], array_values(array_unique($ids[1])), 'Duplicate ids.');
        $this->assertSame([], array_values(array_diff($references[1], $ids[1])), 'Unresolved references.');
    }
}
