<?php

declare(strict_types=1);

namespace DataFixtures;

use App\Entity\Category;
use App\Entity\Link;
use App\Entity\User;
use App\Service\LinkUrlAnalyzer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const string SYMFONY = 'Faire évoluer une app Symfony';
    private const string LLM = 'Intégrer un LLM dans une app';
    private const string AGENT = 'Coder avec un agent IA';
    private const string ARCHITECTURE = 'Structurer une architecture';

    /**
     * Vrais liens relevés le 29/10/2026, aperçus tels que publiés par les sources (balises og).
     * Un aperçu `null` laisse le lien sans aperçu : X ne sert ses balises qu'aux robots qu'il reconnaît.
     * Les liens réservés aux tests E2E sont dans `tests/E2E/RealLinks.php`.
     *
     * @var list<array{
     *     url: string,
     *     categories: list<self::SYMFONY|self::LLM|self::AGENT|self::ARCHITECTURE>,
     *     poster: 'admin@example.com'|'marie@example.com'|'karim@example.com',
     *     daysAgo: int,
     *     context: ?string,
     *     preview: ?array{string, ?string, ?string, ?string},
     * }>
     */
    private const array LINKS = [
        [
            'url' => 'https://github.com/symfony/symfony',
            'categories' => [self::SYMFONY],
            'poster' => 'admin@example.com',
            'daysAgo' => 58,
            'context' => 'La référence pour tout nouveau projet back.',
            'preview' => [
                'GitHub - symfony/symfony: The Symfony PHP framework',
                'The Symfony PHP framework. Contribute to symfony/symfony development by creating an account on GitHub.',
                'https://repository-images.githubusercontent.com/458058/af6a9d00-9374-11e9-887c-917673d9fe68',
                'GitHub',
            ],
        ],
        [
            'url' => 'https://github.com/symfony/ux',
            'categories' => [self::SYMFONY],
            'poster' => 'marie@example.com',
            'daysAgo' => 41,
            'context' => 'Live Components, Turbo, Icons : tout ce qu\'on utilise côté front sur Radar.',
            'preview' => [
                'GitHub - symfony/ux: Symfony UX initiative: a JavaScript ecosystem for Symfony',
                'Symfony UX initiative: a JavaScript ecosystem for Symfony - symfony/ux',
                'https://opengraph.githubassets.com/71e865bf21e177fc5c703f465d1025f2fa70583fc22742981b5f7248c0a2d376/symfony/ux',
                'GitHub',
            ],
        ],
        [
            'url' => 'https://www.youtube.com/watch?v=WiEqxGRg5K4',
            'categories' => [self::SYMFONY, self::LLM],
            'poster' => 'karim@example.com',
            'daysAgo' => 33,
            'context' => 'Le talk SymfonyCon pour voir Symfony AI sur un vrai projet.',
            'preview' => [
                'Symfony AI in Action by Christopher Hertel',
                '🎁 FREE SymfonyCon replay: "Symfony AI in Action" 🎤 Speaker: Christopher Hertel A gift from the Symfony team!In this talk, Christopher Hertel explores how S...',
                'https://i.ytimg.com/vi/WiEqxGRg5K4/maxresdefault.jpg',
                'YouTube',
            ],
        ],
        [
            'url' => 'https://x.com/symfony/status/2115723064927306227',
            'categories' => [self::SYMFONY],
            'poster' => 'admin@example.com',
            'daysAgo' => 1,
            'context' => 'À regarder pour faire dialoguer Messenger avec le service Node.',
            'preview' => ['Symfony on X', '🚀 New in Symfony 8.2: Cross-Application Messaging', null, 'X'],
        ],
        [
            'url' => 'https://symfony.com/blog/symfonycon-warsaw-2026-symfony-mate-from-hallway-idea-to-daily-driver',
            'categories' => [self::SYMFONY, self::AGENT],
            'poster' => 'marie@example.com',
            'daysAgo' => 2,
            'context' => 'Symfony Mate, l\'outil qu\'on a branché sur Radar pour donner le contexte de l\'app aux agents.',
            'preview' => [
                'SymfonyCon Warsaw 2026: Symfony Mate: From Hallway Idea to Daily Driver (Symfony Blog)',
                'Empowering coding agents with application context in practice! SymfonyCon Warsaw 2026 welcomes Johannes Wachter to share the evolution of Symfony Mate, from dropping MCP for a native CLI to shipping A…',
                'https://symfony.com/images/opengraph/symfony.png',
                null,
            ],
        ],
        [
            'url' => 'https://github.com/symfony/ai',
            'categories' => [self::LLM, self::SYMFONY],
            'poster' => 'karim@example.com',
            'daysAgo' => 52,
            'context' => null,
            'preview' => [
                'GitHub - symfony/ai: Symfony AI is a set of components that integrate AI capabilities into PHP applications',
                'Symfony AI is a set of components that integrate AI capabilities into PHP applications - symfony/ai',
                'https://opengraph.githubassets.com/b74be2b8f53b6cbaeaacb9121177ce74748f569a3cf2c976a2f2befa7c08bfe1/symfony/ai',
                'GitHub',
            ],
        ],
        [
            'url' => 'https://github.com/modelcontextprotocol/php-sdk',
            'categories' => [self::LLM],
            'poster' => 'admin@example.com',
            'daysAgo' => 27,
            'context' => 'SDK MCP officiel en PHP, maintenu avec la PHP Foundation.',
            'preview' => [
                'GitHub - modelcontextprotocol/php-sdk: The official PHP SDK for Model Context Protocol servers and clients. Maintained in collaboration with The PHP Foundation.',
                'The official PHP SDK for Model Context Protocol servers and clients. Maintained in collaboration with The PHP Foundation. - modelcontextprotocol/php-sdk',
                'https://opengraph.githubassets.com/eee622682006a8b8a6aa696eb4b689c6b1ba48eb2574fce7bcba31f99469e033/modelcontextprotocol/php-sdk',
                'GitHub',
            ],
        ],
        [
            'url' => 'https://www.youtube.com/watch?v=zjkBMFhNj_g',
            'categories' => [self::LLM],
            'poster' => 'marie@example.com',
            'daysAgo' => 60,
            'context' => 'Une heure pour comprendre ce qu\'il y a sous le capot d\'un LLM, à montrer aux nouveaux.',
            'preview' => [
                '[1hr Talk] Intro to Large Language Models',
                'This is a 1 hour general-audience introduction to Large Language Models: the core technical component behind systems like ChatGPT, Claude, and Bard. What the...',
                'https://i.ytimg.com/vi/zjkBMFhNj_g/maxresdefault.jpg',
                'YouTube',
            ],
        ],
        [
            'url' => 'https://x.com/karpathy/status/2079610838143623371',
            'categories' => [self::LLM],
            'poster' => 'karim@example.com',
            'daysAgo' => 14,
            'context' => null,
            'preview' => [
                'Andrej Karpathy on X',
                'One pattern I find useful for working with LLMs is a nice long ramble session. Sometimes the LLM needs more bits to understand what you\'re trying to achieve, but you\'re too lazy to type them. In these cases I like to lean back, switch to /voice and just ramble for like 10 minutes, total mess, anything goes, full stream of consciousness. Sometimes I declare it up top, something like "switching to speech recognition sorry for any typos...". Sometimes I turn it into a small interview of a few turns. But I find that the LLMs are somehow very good at reconstructing long incoherent rambles and often their echo of your own tangle of thoughts comes out quite a bit cleaner than what you started with. The result is that you improve the mind meld and have to correct things less from that point on.',
                null,
                'X',
            ],
        ],
        [
            'url' => 'https://www.anthropic.com/research/building-effective-agents',
            'categories' => [self::LLM, self::AGENT],
            'poster' => 'admin@example.com',
            'daysAgo' => 45,
            'context' => 'À lire avant de coder le moindre agent : workflows simples d\'abord.',
            'preview' => [
                'Building effective AI agents',
                'Practical guidance from Anthropic on building reliable AI agents, from simple composable patterns to fully autonomous systems.',
                'https://www-cdn.anthropic.com/images/4zrzovbb/website/d356638d9823fd692c42b2477fcac214ed3b236f-1200x630.jpg',
                null,
            ],
        ],
        [
            'url' => 'https://github.com/anthropics/claude-code',
            'categories' => [self::AGENT],
            'poster' => 'karim@example.com',
            'daysAgo' => 38,
            'context' => 'Le dépôt pour suivre les releases et remonter les bugs.',
            'preview' => [
                'GitHub - anthropics/claude-code: Claude Code is an agentic coding tool that lives in your terminal, understands your codebase, and helps you code faster by executing routine tasks, explaining complex code, and handling git workflows - all through natural language commands.',
                'Claude Code is an agentic coding tool that lives in your terminal, understands your codebase, and helps you code faster by executing routine tasks, explaining complex code, and handling git workflo...',
                'https://opengraph.githubassets.com/4a02671b27482d9a63d2ffec4bd21a5b36805d7d723ca7aab757344dad3e3edc/anthropics/claude-code',
                'GitHub',
            ],
        ],
        [
            'url' => 'https://github.com/anthropics/skills',
            'categories' => [self::AGENT],
            'poster' => 'admin@example.com',
            'daysAgo' => 9,
            'context' => 'Des exemples de skills pour s\'inspirer avant d\'écrire les nôtres.',
            'preview' => [
                'GitHub - anthropics/skills: Public repository for Agent Skills',
                'Public repository for Agent Skills. Contribute to anthropics/skills development by creating an account on GitHub.',
                'https://opengraph.githubassets.com/9070f347509922d1b9565f4ff0483b18a6748faf8c9a4e6621820711cd55df0f/anthropics/skills',
                'GitHub',
            ],
        ],
        [
            'url' => 'https://www.youtube.com/watch?v=gv0WHhKelSE',
            'categories' => [self::AGENT],
            'poster' => 'marie@example.com',
            'daysAgo' => 21,
            'context' => null,
            'preview' => [
                'Claude Code best practices | Code w/ Claude',
                'Presented at Code w/ Claude by @anthropic-ai on May 22, 2025 in San Francisco, CA, USA.Speakers:Cal Rueb, Member of Technical Staff at @anthropic-ai',
                'https://i.ytimg.com/vi/gv0WHhKelSE/maxresdefault.jpg',
                'YouTube',
            ],
        ],
        [
            'url' => 'https://x.com/ClaudeDevs/status/2113328526413996100',
            'categories' => [self::AGENT],
            'poster' => 'karim@example.com',
            'daysAgo' => 6,
            'context' => 'Les Agent Skills deviennent un standard ouvert, comme MCP.',
            'preview' => [
                'ClaudeDevs on X',
                'We\'re donating Agent Skills to the Agentic AI Foundation (@AgenticAIFdn), the same foundation we donated MCP to. Since December 2025, 70+ agent platforms have integrated the standard, including ChatGPT, Cursor, GitHub Copilot, and VS Code. Here\'s what that means for you:',
                null,
                'X',
            ],
        ],
        [
            'url' => 'https://code.claude.com/docs/en/best-practices',
            'categories' => [self::AGENT],
            'poster' => 'admin@example.com',
            'daysAgo' => 3,
            'context' => 'Ce qu\'on applique dans nos CLAUDE.md : donner à Claude une vérification à lancer.',
            'preview' => [
                'Best practices for Claude Code - Claude Code Docs',
                'Get more out of Claude Code: tell Claude what it can\'t see, give it a check to run, and enforce the rules that can\'t have exceptions.',
                'https://claude-code.mintlify.app/_mintlify/api/og/v2?division=Use+Claude+Code&title=Best+practices+for+Claude+Code&description=Get+more+out+of+Claude+Code%3A+tell+Claude+what+it+can%27t+see%2C+give+it+a+check+to+run%2C+and+enforce+the+rules+that+can%27t+have+exceptions.&theme=8d9812df31da293aba4e8103&signature=f2417fa5376e77f359bc93e27c1422c620d42d085164f00901f50b2b5194029e',
                'Claude Code Docs',
            ],
        ],
        [
            'url' => 'https://github.com/kgrzybek/modular-monolith-with-ddd',
            'categories' => [self::ARCHITECTURE],
            'poster' => 'marie@example.com',
            'daysAgo' => 49,
            'context' => 'Un monolithe modulaire complet, en .NET mais le découpage se transpose à Symfony.',
            'preview' => [
                'GitHub - kgrzybek/modular-monolith-with-ddd: Full Modular Monolith application with Domain-Driven Design approach.',
                'Full Modular Monolith application with Domain-Driven Design approach. - kgrzybek/modular-monolith-with-ddd',
                'https://opengraph.githubassets.com/c761cfd5c6af8edb0e88bbeb98eb02a6f82c395c95205fe96586db231c5d8e61/kgrzybek/modular-monolith-with-ddd',
                'GitHub',
            ],
        ],
        [
            'url' => 'https://github.com/deptrac/deptrac',
            'categories' => [self::ARCHITECTURE],
            'poster' => 'karim@example.com',
            'daysAgo' => 24,
            'context' => 'Pour faire échouer la CI quand une couche dépend d\'une autre qu\'elle ne devrait pas.',
            'preview' => [
                'GitHub - deptrac/deptrac: Keep your architecture clean.',
                'Keep your architecture clean. Contribute to deptrac/deptrac development by creating an account on GitHub.',
                'https://opengraph.githubassets.com/26c1fd651a1c94ddacd6e878eb60a4114df976991620dbd24195deaef236ed14/deptrac/deptrac',
                'GitHub',
            ],
        ],
        [
            'url' => 'https://www.youtube.com/watch?v=5OjqD-ow8GE',
            'categories' => [self::ARCHITECTURE],
            'poster' => 'admin@example.com',
            'daysAgo' => 35,
            'context' => null,
            'preview' => [
                'Modular Monoliths • Simon Brown • GOTO 2018',
                'This presentation was recorded at GOTO Berlin 2018. #gotocon #gotoberhttp://gotober.comSimon Brown - Creator of C4 Software Architecture model and Author of ...',
                'https://i.ytimg.com/vi/5OjqD-ow8GE/maxresdefault.jpg',
                'YouTube',
            ],
        ],
        [
            'url' => 'https://x.com/simonbrown/status/2066534611287474191',
            'categories' => [self::ARCHITECTURE],
            'poster' => 'marie@example.com',
            'daysAgo' => 17,
            'context' => 'Le livre de référence sur le modèle C4 est sorti.',
            'preview' => null,
        ],
        [
            'url' => 'https://martinfowler.com/bliki/MonolithFirst.html',
            'categories' => [self::ARCHITECTURE],
            'poster' => 'karim@example.com',
            'daysAgo' => 11,
            'context' => 'L\'argument à ressortir la prochaine fois qu\'on propose des microservices dès le départ.',
            'preview' => [
                'bliki: Monolith First',
                'Going directly to a microservices architecture is risky, so consider building a monolithic system first. Split to microservices when, and if, you need it.',
                'https://martinfowler.com/bliki/images/microservice-verdict/path.png',
                'martinfowler.com',
            ],
        ],
    ];

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly LinkUrlAnalyzer $urlAnalyzer,
    ) {}

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);

        $users = [
            'admin@example.com' => $user,
            'marie@example.com' => $this->member('marie@example.com'),
            'karim@example.com' => $this->member('karim@example.com'),
        ];
        foreach ($users as $member) {
            $manager->persist($member);
        }

        $categories = [];
        foreach ([self::SYMFONY, self::LLM, self::AGENT, self::ARCHITECTURE] as $name) {
            $category = new Category($name, new \DateTimeImmutable());
            $manager->persist($category);
            $categories[] = $category;
        }

        foreach (self::LINKS as $data) {
            $link = new Link(
                $data['url'],
                $this->urlAnalyzer->normalize($data['url']),
                array_filter($categories, static fn(Category $category): bool => in_array(
                    $category->getName(),
                    $data['categories'],
                    strict: true,
                )),
                $users[$data['poster']] ?? $user,
                new \DateTimeImmutable(sprintf('-%d days', $data['daysAgo'])),
                $this->urlAnalyzer->sourceType($data['url']),
                $data['context'],
            );

            $manager->persist($link);

            if (null === $data['preview']) {
                $link->markPreviewUnavailable();
                continue;
            }

            [$title, $description, $imageUrl, $siteName] = $data['preview'];
            // Mêmes longueurs maximales que le parser d'aperçu
            $link->applyPreview(
                mb_substr($title, start: 0, length: 255),
                null === $description ? null : mb_substr($description, start: 0, length: 500),
                $imageUrl,
                $siteName,
            );
        }

        $manager->flush();
    }

    /** @param non-empty-string $email */
    private function member(string $email): User
    {
        $member = new User();
        $member->setEmail($email);
        $member->setPassword($this->passwordHasher->hashPassword($member, 'password'));

        return $member;
    }
}
